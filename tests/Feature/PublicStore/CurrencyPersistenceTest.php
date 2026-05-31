<?php

namespace Tests\Feature\PublicStore;

use App\Models\Customer;
use App\Models\User;
use App\Services\GeoIPService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_detects_currency_on_registration()
    {
        Http::fake([
            'ipapi.co/*/json/' => Http::response(['country_code' => 'US']),
        ]);

        $response = $this->post(route('public-store.portal.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('public-store.portal.dashboard'));

        $customer = Customer::withoutGlobalScopes()->where('email', 'john@example.com')->first();
        $this->assertEquals('USD', $customer->currency);
    }

    /** @test */
    public function it_detects_currency_on_login_if_null()
    {
        Http::fake([
            'ipapi.co/*/json/' => Http::response(['country_code' => 'US']),
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'Existing Customer',
            'email' => 'existing@example.com',
            'password' => Hash::make('Password123'),
            'currency' => null,
            'user_id' => $this->user->id,
        ]);

        $response = $this->post(route('public-store.portal.login'), [
            'email' => 'existing@example.com',
            'password' => 'Password123',
        ]);

        $response->assertRedirect();

        $customer->refresh();
        $this->assertEquals('USD', $customer->currency);
    }

    /** @test */
    public function it_does_not_overwrite_currency_on_login_if_already_set()
    {
        Http::fake([
            'ipapi.co/*/json/' => Http::response(['country_code' => 'US']), // Would suggest USD
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'Existing Customer',
            'email' => 'existing@example.com',
            'password' => Hash::make('Password123'),
            'currency' => 'CAD', // Already set to CAD
            'user_id' => $this->user->id,
        ]);

        $response = $this->post(route('public-store.portal.login'), [
            'email' => 'existing@example.com',
            'password' => 'Password123',
        ]);

        $customer->refresh();
        $this->assertEquals('CAD', $customer->currency); // Stays CAD
    }

    /** @test */
    public function it_detects_currency_on_set_password()
    {
        Http::fake([
            'ipapi.co/*/json/' => Http::response(['country_code' => 'US']),
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'New Customer',
            'email' => 'new@example.com',
            'currency' => null,
            'user_id' => $this->user->id,
            'remember_token' => 'valid-token',
            'magic_link_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post(route('public-store.portal.set-password'), [
            'token' => 'valid-token',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect();

        $customer->refresh();
        $this->assertEquals('USD', $customer->currency);
    }

    /** @test */
    public function it_allows_manual_currency_update()
    {
        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'Test Customer',
            'email' => 'test@example.com',
            'currency' => 'CAD',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->put(route('public-store.portal.profile'), [
                'currency' => 'USD',
            ]);

        $response->assertStatus(200);

        $customer->refresh();
        $this->assertEquals('USD', $customer->currency);
    }

    /** @test */
    public function it_validates_currency_on_update()
    {
        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'Test Customer',
            'email' => 'test@example.com',
            'currency' => 'CAD',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->put(route('public-store.portal.profile'), [
                'currency' => 'INVALID',
            ]);

        $response->assertSessionHasErrors('currency');

        $customer->refresh();
        $this->assertEquals('CAD', $customer->currency);
    }
}
