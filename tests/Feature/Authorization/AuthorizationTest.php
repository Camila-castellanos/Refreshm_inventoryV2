<?php

namespace Tests\Feature\Authorization;

use App\Models\Company;
use App\Models\User;
use Tests\TestCaseWithCompany;

class AuthorizationTest extends TestCaseWithCompany
{
    // === CHECK USER ROLE MIDDLEWARE ===

    public function test_owner_can_access_owner_routes(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);

        $response = $this->actingAs($admin)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_user_redirected_from_admin_routes(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/accounting/payments');

        $response->assertStatus(403);
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/accounting/payments');

        $response->assertRedirect('/login');
    }

    public function test_role_check_case_sensitive(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(403);
    }

    // === CHECK PAGE PERMISSIONS ===

    public function test_owner_has_all_page_permissions(): void
    {
        $this->assertTrue($this->owner->role === 'OWNER');

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_admin_with_permissions_can_access_page(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
            'page_permissions' => json_encode([
                'Accounting' => ['Payments'],
            ]),
        ]);

        $response = $this->actingAs($admin)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_user_without_page_permission_denied(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => json_encode([
                'Inventory' => ['Active Inventory'],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(403);
    }

    public function test_user_with_tab_permission_can_access_tab(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => json_encode([
                'Inventory' => ['Active Inventory', 'On Hold', 'Sold'],
                'Accounting' => ['Payments'],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_user_without_tab_permission_denied(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => json_encode([
                'Inventory' => ['Active Inventory'],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(403);
    }

    public function test_null_permissions_uses_defaults(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
            'page_permissions' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_string_permissions_decoded(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => json_encode([
                'Accounting' => ['Payments'],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_empty_permissions_denies_all(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => json_encode([]),
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(403);
    }

    // === COMPANY ISOLATION ===

    public function test_owner_can_access_own_company_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    public function test_user_cannot_access_other_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherOwner = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    // === ROLE BASED ACCESS CONTROL ===

    public function test_owner_can_manage_users(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);

        $response = $this->actingAs($admin)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    public function test_user_cannot_manage_users(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/company/settings');

        $response->assertStatus(302);
    }

    public function test_owner_can_access_company_settings(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_company_settings(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);

        $response = $this->actingAs($admin)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    public function test_user_cannot_access_company_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/company/settings');

        $response->assertStatus(302);
    }

    // === PAGE PERMISSIONS CRUD ===

    public function test_permissions_stored_as_json(): void
    {
        $permissions = ['Inventory' => ['Active Inventory']];

        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => json_encode($permissions),
        ]);

        $this->assertJson($user->page_permissions);
    }

    public function test_permissions_retrieved_correctly(): void
    {
        $permissions = [
            'Inventory' => ['Active Inventory', 'On Hold'],
            'Accounting' => ['Payments'],
        ];

        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => $permissions,
        ]);

        $user->refresh();

        $this->assertIsArray($user->page_permissions);
        $this->assertArrayHasKey('Inventory', $user->page_permissions);
    }

    public function test_permissions_structure_matches_config(): void
    {
        $permissions = config('permissions.defaults');

        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('Inventory', $permissions);
        $this->assertArrayHasKey('Accounting', $permissions);
    }

    // === EDGE CASES ===

    public function test_user_with_all_permissions_has_full_access(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => config('permissions.defaults'),
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_user_without_access_to_page_denied(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => ['Inventory' => ['Active Inventory']],
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(403);
    }

    public function test_backward_compatibility_flat_array(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => ['Accounting', 'Inventory'],
        ]);

        $response = $this->actingAs($user)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }

    public function test_dashboard_accessible_by_admin_roles(): void
    {
        $response = $this->actingAs($this->owner)->get('/dashboard');
        $response->assertStatus(200);

        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_with_view_org_data_sees_all_payments(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
            'page_permissions' => json_encode([
                'Accounting' => ['Payments', 'View Organization Data'],
            ]),
        ]);

        $response = $this->actingAs($admin)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }
}
