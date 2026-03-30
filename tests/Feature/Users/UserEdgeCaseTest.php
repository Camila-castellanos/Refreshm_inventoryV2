<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Tests\TestCaseWithCompany;

class UserEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_user_with_duplicate_email_fails_at_db_level(): void
    {
        $existingUser = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/users', [
                'name' => 'New User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_user_with_password_too_short_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/users', [
                'name' => 'Test User',
                'email' => 'test'.uniqid().'@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_create_user_with_password_mismatch_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/users', [
                'name' => 'Test User',
                'email' => 'test'.uniqid().'@example.com',
                'password' => 'password123',
                'password_confirmation' => 'differentpassword',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_user_with_duplicate_email_fails_at_db_level(): void
    {
        $user1 = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'email' => 'user1@example.com',
        ]);

        $user2 = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'email' => 'user2@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/users/{$user2->id}", [
                'name' => 'Updated User',
                'email' => 'user1@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(500);
    }

    public function test_update_user_without_changing_password_succeeds(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'name' => 'Original Name',
            'password' => bcrypt('originalpassword'),
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
    }

    public function test_delete_last_admin_user_allows(): void
    {
        $adminUser = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'role' => 'ADMIN',
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/users/{$adminUser->id}");

        $response->assertStatus(200);
    }

    public function test_delete_self_allows(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete("/users/{$this->owner->id}");

        $response->assertStatus(200);
    }

    public function test_update_timezone_with_invalid_timezone_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/timezone', [
                'timezone' => 'Invalid/Timezone',
            ]);

        $response->assertSessionHasErrors('timezone');
    }

    public function test_update_role_with_invalid_role_allows(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/users/{$user->id}/role", [
                'role' => 'INVALID_ROLE',
            ]);

        $response->assertStatus(200);
    }

    public function test_access_users_as_non_admin_returns_403(): void
    {
        $regularUser = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($regularUser)
            ->get('/users');

        $response->assertStatus(403);
    }

    public function test_update_headers_with_invalid_tab_returns_500(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/users/{$user->id}/headers", [
                'tab' => 'invalid_tab',
                'fields' => ['manufacturer', 'model'],
            ]);

        $response->assertStatus(500);
    }

    public function test_update_printable_tag_fields_with_empty_array_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/printable-tag-fields', [
                'fields' => [],
            ]);

        $response->assertStatus(302);
    }

    public function test_update_printable_invoice_fields_with_empty_array_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/printable-invoice-fields', [
                'fields' => [],
            ]);

        $response->assertStatus(302);
    }

    public function test_update_tab_name_with_nonexistent_tab_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/tabs/update-name', [
                'tab_id' => 99999,
                'name' => 'New Tab Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_get_invoice_logo_when_not_exists_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/user/invoice-logo?strict=true');

        $response->assertStatus(404);
    }

    public function test_update_invoice_logo_with_invalid_file_type_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/user/invoice-logo', [
                'photo' => 'not_a_file',
            ]);

        $response->assertStatus(302);
    }

    public function test_update_user_with_empty_name_fails(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/users/{$user->id}", [
                'name' => '',
                'email' => $user->email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_user_with_name_too_long_fails(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->put("/users/{$user->id}", [
                'name' => $longName,
                'email' => $user->email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_user_with_name_too_long_fails(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->post('/users', [
                'name' => $longName,
                'email' => 'test'.uniqid().'@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_user_with_valid_max_length_name_succeeds(): void
    {
        $maxLengthName = str_repeat('A', 255);

        $response = $this->actingAs($this->owner)
            ->post('/users', [
                'name' => $maxLengthName,
                'email' => 'test'.uniqid().'@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(201);
    }
}
