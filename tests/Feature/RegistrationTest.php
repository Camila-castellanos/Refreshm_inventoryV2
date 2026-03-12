<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register_as_admin_when_creating_company(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'companyName' => 'Test Company',
            'invitation' => false,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals('ADMIN', $user->role);

        // Verify default permissions from config are applied
        $this->assertEquals(config('permissions.defaults'), $user->page_permissions);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_register_as_user_when_joining_via_invitation(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $company = Company::factory()->create(['name' => 'Existing Company']);

        $response = $this->post('/register', [
            'name' => 'Invited User',
            'email' => 'invited@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'companyName' => 'Existing Company',
            'invitation' => true,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();

        $user = User::where('email', 'invited@example.com')->first();
        $this->assertEquals('USER', $user->role);

        // Verify user_defaults from config are applied for invited members
        $this->assertEquals(config('permissions.user_defaults'), $user->page_permissions);
    }
}
