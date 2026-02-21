<?php

namespace Tests\Feature\Invitations;

use App\Models\Company;
use App\Models\User;
use Tests\TestCaseWithCompany;

class InvitationTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $response = $this->get('/invitation');

        $response->assertRedirect();
    }

    public function test_index_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/invitation');

        $response->assertStatus(200);
    }

    public function test_index_passes_company_query_param(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user)
            ->get('/invitation?company='.urlencode($company->name));

        $response->assertStatus(200);
    }

    public function test_accept_associates_user_with_company(): void
    {
        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $company = Company::factory()->create();

        $response = $this->actingAs($user)
            ->post('/invitation', [
                'companyName' => $company->name,
            ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals($company->id, $user->company_id);
    }

    public function test_accept_fails_with_invalid_company(): void
    {
        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->post('/invitation', [
                'companyName' => 'NonExistentCompany12345',
            ]);

        $response->assertSessionHasErrors('companyName');
    }

    public function test_accept_requires_company_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/invitation', []);

        $response->assertSessionHasErrors('companyName');
    }

    public function test_accept_redirects_to_profile(): void
    {
        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $company = Company::factory()->create();

        $response = $this->actingAs($user)
            ->post('/invitation', [
                'companyName' => $company->name,
            ]);

        $response->assertRedirectToRoute('profile.show');
    }

    public function test_accept_already_has_company_updates_company(): void
    {
        $oldCompany = Company::factory()->create();
        $newCompany = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $oldCompany->id,
        ]);

        $response = $this->actingAs($user)
            ->post('/invitation', [
                'companyName' => $newCompany->name,
            ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals($newCompany->id, $user->company_id);
    }
}
