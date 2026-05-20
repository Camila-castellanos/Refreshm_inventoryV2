<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Services\MagicLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customer_can_register(): void
    {
        Mail::fake();

        // Create a system user for portal customers (since customers require user_id)
        $user = \App\Models\User::factory()->create();

        $response = $this->post('/publicstore/account/register', [
            'name' => 'Juan Perez',
            'email' => 'juan@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/publicstore/account/dashboard');
        $this->assertDatabaseHas('customers', ['email' => 'juan@test.com']);

        // Check customer is authenticated
        $customer = Customer::where('email', 'juan@test.com')->first();
        $this->assertNotNull($customer);
        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertEquals($customer->id, Auth::guard('customer')->id());
    }

    public function test_duplicate_email_returns_validation_error(): void
    {
        Customer::factory()->create(['email' => 'existe@test.com']);

        $response = $this->post('/publicstore/account/register', [
            'name' => 'Otro Nombre',
            'email' => 'existe@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        // Inertia validation redirects back with errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_existing_customer_without_password_receives_magic_link(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create(['password' => null]);

        $response = $this->post('/publicstore/account/login', [
            'email' => $customer->email,
        ]);

        // back()->with() redirects back after POST, so we follow it
        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Revisa tu email para el enlace.');
        Mail::assertSent(\App\Mail\MagicLinkEmail::class);
    }

    public function test_email_not_found_returns_generic_message(): void
    {
        $response = $this->post('/publicstore/account/login', [
            'email' => 'noexiste@test.com',
        ]);

        // back()->with() redirects back after POST, so we follow it
        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Si tu email está registrado, recibirás un enlace.');
    }

    public function test_customer_with_password_cannot_use_magic_link(): void
    {
        $customer = Customer::factory()->create(['password' => Hash::make('Password123')]);

        $response = $this->post('/publicstore/account/login', [
            'email' => $customer->email,
        ]);

        // back()->withErrors() redirects back after validation error
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_set_password_creates_password_and_invalidates_token(): void
    {
        $customer = Customer::factory()->create([
            'password' => null,
            'remember_token' => 'valid-token',
            'magic_link_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/publicstore/account/set-password', [
            'token' => 'valid-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/publicstore/account/dashboard');

        // Token should be consumed and password set
        $customer->refresh();
        $this->assertNull($customer->remember_token);
        $this->assertNull($customer->magic_link_expires_at);
        $this->assertTrue(Hash::check('NewPassword123', $customer->password));

        // Customer should be logged in
        $this->assertTrue(Auth::guard('customer')->check());
    }

    public function test_expired_token_returns_error(): void
    {
        $customer = Customer::factory()->create([
            'password' => null,
            'remember_token' => 'expired-token',
            'magic_link_expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->post('/publicstore/account/set-password', [
            'token' => 'expired-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['token']);
    }

    public function test_invalid_token_returns_error(): void
    {
        $response = $this->post('/publicstore/account/set-password', [
            'token' => 'non-existent-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['token']);
    }

    public function test_authenticated_customer_can_logout(): void
    {
        $customer = Customer::factory()->create(['password' => Hash::make('Password123')]);

        $this->actingAs($customer, 'customer');

        $response = $this->post('/publicstore/account/logout');

        $response->assertStatus(302);
    }

    public function test_show_set_password_page_loads(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'remember_token' => 'valid-token',
            'magic_link_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->get('/publicstore/account/magic-link/valid-token');

        $response->assertStatus(200);
    }

    public function test_show_set_password_returns_error_for_expired_token(): void
    {
        $customer = Customer::factory()->create([
            'remember_token' => 'expired-token',
            'magic_link_expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->get('/publicstore/account/magic-link/expired-token');

        $response->assertStatus(200);
    }

    public function test_customer_can_update_profile(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customer')
            ->putJson('/publicstore/account/profile', [
                'default_store' => 'My Store',
                'default_shipping' => '5',
                'notes' => 'Test notes',
            ]);

        $response->assertOk();

        $customer->refresh();
        $this->assertEquals('My Store', $customer->default_store);
        $this->assertEquals('5', $customer->default_shipping);
        $this->assertEquals('Test notes', $customer->notes);
    }

    public function test_update_profile_validates_input(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customer')
            ->putJson('/publicstore/account/profile', [
                'default_store' => str_repeat('a', 300),
            ]);

        $response->assertUnprocessable();
    }

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $response = $this->putJson('/publicstore/account/profile', [
            'default_store' => 'Test',
        ]);

        $response->assertUnauthorized();
    }
}