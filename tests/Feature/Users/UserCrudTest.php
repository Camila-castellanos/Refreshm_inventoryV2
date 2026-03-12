<?php

namespace Tests\Feature\Users;

use App\Models\Tab;
use App\Models\User;
use Tests\TestCaseWithCompany;

class UserCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_users_for_owner(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/users');

        $response->assertStatus(200);
    }

    public function test_index_returns_users_with_filter_all(): void
    {
        User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/users?filter=all');

        $response->assertStatus(200);
    }

    public function test_create_returns_create_page(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/users/create');

        $response->assertStatus(200);
    }

    public function test_store_creates_user_as_admin_by_default(): void
    {
        $email = 'testuser'.uniqid().'@example.com';
        $data = [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/users', $data);

        $response->assertStatus(201);

        $user = User::where('email', $email)->first();
        $this->assertEquals('ADMIN', $user->role);
        $this->assertEquals('Test User', $user->name);

        // Verify default permissions from config are applied
        $this->assertEquals(config('permissions.defaults'), $user->page_permissions);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/users', []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_store_validates_email_format(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/users', $data);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_edit_returns_edit_page(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get("/users/{$user->id}/edit");

        $response->assertStatus(200);
    }

    public function test_update_modifies_user(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->actingAs($this->owner)
            ->put("/users/{$user->id}", $data);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
    }

    public function test_destroy_deletes_user(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/users/{$user->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_change_role_returns_view(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get("/users/{$user->id}/role");

        $response->assertStatus(200);
    }

    public function test_update_role_modifies_user_role(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/users/{$user->id}/role", [
                'role' => 'ADMIN',
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('ADMIN', $user->role);
    }

    public function test_update_headers(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->owner->company_id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/users/{$user->id}/headers", [
                'tab' => 'sold',
                'fields' => ['manufacturer', 'model', 'storage'],
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $soldHeaders = json_decode($user->sold_headers, true);
        $this->assertContains('manufacturer', $soldHeaders);
    }

    public function test_update_timezone(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/timezone', [
                'timezone' => 'America/New_York',
            ]);

        $response->assertStatus(302);

        $this->owner->refresh();
        $this->assertEquals('America/New_York', $this->owner->timezone);
    }

    public function test_get_timezone(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/user/timezone');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('timezone', $data);
    }

    public function test_get_printable_tag_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/user/printable-tag-fields');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function test_update_printable_tag_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/printable-tag-fields', [
                'fields' => ['manufacturer', 'model', 'storage', 'colour'],
            ]);

        $response->assertStatus(200);

        $this->owner->refresh();
        $this->assertIsArray($this->owner->printable_tag_fields);
    }

    public function test_get_printable_invoice_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/user/printable-invoice-fields');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function test_update_printable_invoice_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/printable-invoice-fields', [
                'fields' => ['header', 'footer', 'logo', 'total'],
            ]);

        $response->assertStatus(200);

        $this->owner->refresh();
        $this->assertIsArray($this->owner->printable_invoice_fields);
    }

    public function test_user_tabs_returns_tabs(): void
    {
        Tab::factory()->forOwner($this->owner->id)->create(['name' => 'Tab 1']);
        Tab::factory()->forOwner($this->owner->id)->create(['name' => 'Tab 2']);

        $response = $this->actingAs($this->owner)
            ->get('/user/tabs');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('tabs', $data);
    }
}
