<?php

namespace Tests\Feature\Company;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCaseWithCompany;

class CompanyCrudTest extends TestCaseWithCompany
{
    use RefreshDatabase;

    protected function getCompanyUser(): User
    {
        return $this->owner;
    }

    protected function getAdminUser(): User
    {
        return User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);
    }

    protected function getRegularUser(): User
    {
        return User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);
    }

    // === COMPANY SETTINGS ===

    public function test_owner_can_view_company_settings(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->get('/company/settings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('company')
            ->has('members')
        );
    }

    public function test_admin_can_view_company_settings(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)
            ->get('/company/settings');

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_view_company_settings(): void
    {
        $user = $this->getRegularUser();

        $response = $this->actingAs($user)
            ->get('/company/settings');

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_owner_can_update_company_name(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', [
                'name' => 'New Company Name',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'New Company Name',
        ]);
    }

    public function test_admin_can_update_company_name(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)
            ->put('/company/settings', [
                'name' => 'Updated Company Name',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'Updated Company Name',
        ]);
    }

    public function test_regular_user_cannot_update_company_name(): void
    {
        $user = $this->getRegularUser();

        $response = $this->actingAs($user)
            ->put('/company/settings', [
                'name' => 'Hacked Company Name',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_company_name_validation_required(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', [
                'name' => '',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_company_name_validation_unique(): void
    {
        $otherCompany = \App\Models\Company::factory()->create([
            'name' => 'Existing Company',
        ]);

        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', [
                'name' => 'Existing Company',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_company_name_validation_unique_ignores_current(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', [
                'name' => $this->company->name,
            ]);

        $response->assertSessionHasNoErrors();
    }

    // === MEMBER MANAGEMENT ===

    public function test_owner_can_add_member_with_admin_role(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'ADMIN',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'role' => 'ADMIN',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_owner_can_add_member_with_user_role(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'USER',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_admin_can_add_member(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)
            ->post('/company/members', [
                'name' => 'Member Added By Admin',
                'email' => 'memberbyadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_regular_user_cannot_add_member(): void
    {
        $user = $this->getRegularUser();

        $response = $this->actingAs($user)
            ->post('/company/members', [
                'name' => 'Unauthorized Member',
                'email' => 'unauthorized@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_member_add_validates_unique_email(): void
    {
        $existingUser = $this->getRegularUser();

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Duplicate Email User',
                'email' => $existingUser->email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_member_add_validates_password_confirmed(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Unconfirmed Password User',
                'email' => 'unconfirmed@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different_password',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_member_add_validates_required_fields(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_member_add_validates_role_values(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Invalid Role User',
                'email' => 'invalidrole@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'INVALID_ROLE',
            ]);

        $response->assertSessionHasErrors(['role']);
    }

    public function test_owner_can_update_member_role(): void
    {
        $member = $this->getRegularUser();

        $response = $this->actingAs($this->getCompanyUser())
            ->put("/company/members/{$member->id}", [
                'role' => 'ADMIN',
            ]);

        $response->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertEquals('ADMIN', $member->role);
    }

    public function test_owner_cannot_modify_own_role(): void
    {
        $owner = $this->getCompanyUser();

        $response = $this->actingAs($owner)
            ->put("/company/members/{$owner->id}", [
                'role' => 'USER',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_member_role(): void
    {
        $admin = $this->getAdminUser();
        $member = $this->getRegularUser();

        $response = $this->actingAs($admin)
            ->put("/company/members/{$member->id}", [
                'role' => 'ADMIN',
            ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_regular_user_cannot_update_member_role(): void
    {
        $user = $this->getRegularUser();
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($user)
            ->put("/company/members/{$otherUser->id}", [
                'role' => 'ADMIN',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_member_role_update_validates_role_values(): void
    {
        $member = $this->getRegularUser();

        $response = $this->actingAs($this->getCompanyUser())
            ->put("/company/members/{$member->id}", [
                'role' => 'INVALID',
            ]);

        $response->assertSessionHasErrors(['role']);
    }

    public function test_owner_can_update_member_permissions(): void
    {
        $member = $this->getRegularUser();
        $permissions = ['inventory' => true, 'sales' => false];

        $response = $this->actingAs($this->getCompanyUser())
            ->put("/company/members/{$member->id}/permissions", [
                'permissions' => $permissions,
            ]);

        $response->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertEquals($permissions, $member->page_permissions);
    }

    public function test_owner_can_update_member_permissions_empty(): void
    {
        $member = $this->getRegularUser();

        $response = $this->actingAs($this->getCompanyUser())
            ->put("/company/members/{$member->id}/permissions", [
                'permissions' => [],
            ]);

        $response->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertEquals([], $member->page_permissions);
    }

    public function test_owner_cannot_modify_own_permissions(): void
    {
        $owner = $this->getCompanyUser();

        $response = $this->actingAs($owner)
            ->put("/company/members/{$owner->id}/permissions", [
                'permissions' => ['inventory' => false],
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_member_permissions(): void
    {
        $user = $this->getRegularUser();
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($user)
            ->put("/company/members/{$otherUser->id}/permissions", [
                'permissions' => ['inventory' => true],
            ]);

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_owner_can_remove_member(): void
    {
        $member = $this->getRegularUser();

        $response = $this->actingAs($this->getCompanyUser())
            ->delete("/company/members/{$member->id}");

        $response->assertSessionHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $member->id]);
    }

    public function test_owner_cannot_remove_self(): void
    {
        $owner = $this->getCompanyUser();

        $response = $this->actingAs($owner)
            ->delete("/company/members/{$owner->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_remove_member(): void
    {
        $admin = $this->getAdminUser();
        $member = $this->getRegularUser();

        $response = $this->actingAs($admin)
            ->delete("/company/members/{$member->id}");

        $response->assertSessionHasNoErrors();
    }

    public function test_regular_user_cannot_remove_member(): void
    {
        $user = $this->getRegularUser();
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($user)
            ->delete("/company/members/{$otherUser->id}");

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_removed_member_cannot_access_company(): void
    {
        $member = $this->getRegularUser();

        $this->actingAs($this->getCompanyUser())
            ->delete("/company/members/{$member->id}");

        $response = $this->actingAs($member)
            ->get('/company/settings');

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_cannot_modify_member_from_different_company(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($this->getCompanyUser())
            ->put("/company/members/{$otherUser->id}", [
                'role' => 'ADMIN',
            ]);

        $response->assertStatus(404);
    }

    // === INVOICE LOGO ===

    public function test_owner_can_upload_logo(): void
    {
        Storage::fake('local');

        $file = new UploadedFile(
            base_path('tests/Feature/Company/logo-test.jpg'),
            'logo.jpg',
            'image/jpeg',
            null,
            true
        );

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', [
                'photo' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNotNull($this->company->logo);
    }

    public function test_admin_can_upload_logo(): void
    {
        Storage::fake('local');

        $file = new UploadedFile(
            base_path('tests/Feature/Company/logo-test.png'),
            'logo.png',
            'image/png',
            null,
            true
        );

        $response = $this->actingAs($this->getAdminUser())
            ->post('/company/invoice-logo', [
                'photo' => $file,
            ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_logo_validates_mime_types(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', [
                'photo' => $file,
            ]);

        $response->assertSessionHasErrors(['photo']);
    }

    public function test_logo_validates_max_size(): void
    {
        $content = str_repeat('A', 2049);
        $file = UploadedFile::fake()->create('large-logo.jpg', 2049);

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', [
                'photo' => $file,
            ]);

        $response->assertSessionHasErrors(['photo']);
    }

    public function test_upload_new_logo_replaces_old_logo(): void
    {
        Storage::fake('local');

        $this->company->logo = 'company-logos/old-logo.jpg';
        $this->company->save();

        $oldLogoPath = $this->company->logo;

        $file = new UploadedFile(
            base_path('tests/Feature/Company/logo-test.jpg'),
            'new-logo.jpg',
            'image/jpeg',
            null,
            true
        );

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', [
                'photo' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNotEquals($oldLogoPath, $this->company->logo);
        $this->assertStringContainsString('company-logos', $this->company->logo);
    }

    public function test_owner_can_delete_logo(): void
    {
        Storage::fake('local');

        $this->company->logo = 'company-logos/test-logo.jpg';
        $this->company->save();

        $response = $this->actingAs($this->getCompanyUser())
            ->delete('/company/invoice-logo');

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNull($this->company->logo);
    }

    public function test_admin_can_delete_logo(): void
    {
        Storage::fake('local');

        $this->company->logo = 'company-logos/test-logo.jpg';
        $this->company->save();

        $response = $this->actingAs($this->getAdminUser())
            ->delete('/company/invoice-logo');

        $response->assertSessionHasNoErrors();
    }

    public function test_regular_user_cannot_delete_logo(): void
    {
        $response = $this->actingAs($this->getRegularUser())
            ->delete('/company/invoice-logo');

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    public function test_get_logo_returns_json_when_no_logo(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->get('/company/invoice-logo');

        $response->assertStatus(200);
        $response->assertJson(['url' => null]);
    }

    public function test_get_logo_returns_404_when_logo_file_missing(): void
    {
        $this->company->logo = 'company-logos/nonexistent.jpg';
        $this->company->save();

        $response = $this->actingAs($this->getCompanyUser())
            ->get('/company/invoice-logo');

        $response->assertStatus(404);
        $response->assertJson(['url' => null]);
    }

    // === GUEST (UNAUTHENTICATED) TESTS ===

    public function test_guest_cannot_view_company_settings(): void
    {
        $response = $this->get('/company/settings');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_update_company_settings(): void
    {
        $response = $this->put('/company/settings', [
            'name' => 'Hacked Company',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_add_member(): void
    {
        $response = $this->post('/company/members', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'USER',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_upload_logo(): void
    {
        $file = new UploadedFile(
            base_path('tests/Feature/Company/logo-test.jpg'),
            'logo.jpg',
            'image/jpeg',
            null,
            true
        );

        $response = $this->post('/company/invoice-logo', [
            'photo' => $file,
        ]);

        $response->assertRedirect('/login');
    }
}
