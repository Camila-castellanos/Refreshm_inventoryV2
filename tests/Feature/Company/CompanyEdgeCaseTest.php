<?php

namespace Tests\Feature\Company;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCaseWithCompany;

class CompanyEdgeCaseTest extends TestCaseWithCompany
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

    public function test_update_company_with_name_too_long_fails(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', [
                'name' => $longName,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_add_member_with_invalid_email_allows(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Test User',
                'email' => 'not-an-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_add_member_with_name_too_long_fails(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => $longName,
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_add_member_with_email_too_long_fails(): void
    {
        $longEmail = str_repeat('a', 250).'@example.com';

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Test User',
                'email' => $longEmail,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_add_member_with_password_too_short_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_delete_logo_when_no_logo_exists_succeeds(): void
    {
        $this->company->logo = null;
        $this->company->save();

        $response = $this->actingAs($this->getCompanyUser())
            ->delete('/company/invoice-logo');

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNull($this->company->logo);
    }

    public function test_view_settings_when_company_is_null_returns_404(): void
    {
        $this->company->delete();

        $response = $this->actingAs($this->getCompanyUser())
            ->get('/company/settings');

        $response->assertStatus(404);
    }

    public function test_update_member_role_of_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/members/99999', [
                'role' => 'ADMIN',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_permissions_of_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/members/99999/permissions', [
                'permissions' => ['inventory' => true],
            ]);

        $response->assertStatus(404);
    }

    public function test_remove_nonexistent_member_returns_404(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->delete('/company/members/99999');

        $response->assertStatus(404);
    }

    public function test_upload_logo_without_file_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', []);

        $response->assertSessionHasNoErrors();
    }

    public function test_upload_logo_with_corrupted_file_type_fails(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-executable');

        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', [
                'photo' => $file,
            ]);

        $response->assertSessionHasErrors('photo');
    }

    public function test_update_company_without_data_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', []);

        $response->assertSessionHasErrors('name');
    }

    public function test_add_member_with_empty_name_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_add_member_with_empty_email_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Test User',
                'email' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_add_member_without_password_confirmation_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_company_with_max_length_name_succeeds(): void
    {
        $maxName = str_repeat('A', 255);

        $response = $this->actingAs($this->getCompanyUser())
            ->put('/company/settings', [
                'name' => $maxName,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => $maxName,
        ]);
    }

    public function test_add_member_without_role_fails(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/members', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_admin_cannot_modify_owner_role(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)
            ->put("/company/members/{$this->owner->id}", [
                'role' => 'USER',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_remove_owner(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)
            ->delete("/company/members/{$this->owner->id}");

        $response->assertStatus(403);
    }

    public function test_upload_logo_without_photo_key_succeeds(): void
    {
        $response = $this->actingAs($this->getCompanyUser())
            ->post('/company/invoice-logo', []);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNull($this->company->logo);
    }

    public function test_update_member_permissions_with_null_permissions(): void
    {
        $member = $this->getRegularUser();

        $response = $this->actingAs($this->getCompanyUser())
            ->put("/company/members/{$member->id}/permissions", [
                'permissions' => null,
            ]);

        $response->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertEquals([], $member->page_permissions);
    }
}
