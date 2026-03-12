<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_company_registration_creates_default_storage(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'companyName' => 'New Tech Company',
            'invitation' => false,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertStatus(302);

        $user = User::where('email', 'owner@example.com')->first();
        $this->assertNotNull($user->company_id);

        $company = Company::find($user->company_id);
        $this->assertEquals('New Tech Company', $company->name);

        // Verify default storage was created for the company
        $storage = Storage::withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertNotNull($storage, 'Default storage should be created for new company');
        $this->assertEquals('Default Storage', $storage->name);
        $this->assertTrue((bool) $storage->is_default);
    }
}
