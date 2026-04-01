<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    public function test_user_belongs_to_company(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $user->company);
    }

    public function test_user_has_one_owned_company(): void
    {
        $owner = User::factory()->create([
            'role' => 'OWNER',
        ]);
        $company = Company::factory()->create([
            'owner_id' => $owner->id,
        ]);

        $this->assertInstanceOf(Company::class, $owner->ownedCompany);
    }

    public function test_user_has_many_stores(): void
    {
        $user = User::factory()->create();

        \App\Models\Store::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->stores);
    }

    public function test_user_has_many_api_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test-token');

        $this->assertCount(1, $user->tokens);
    }

    public function test_user_page_permissions_cast_to_array(): void
    {
        $user = User::factory()->create([
            'page_permissions' => ['inventory' => true, 'sales' => false],
        ]);

        $this->assertIsArray($user->page_permissions);
    }

    public function test_user_printable_tag_fields_cast_to_array(): void
    {
        $user = User::factory()->create([
            'printable_tag_fields' => ['manufacturer', 'model'],
        ]);

        $this->assertIsArray($user->printable_tag_fields);
    }

    public function test_user_printable_invoice_fields_cast_to_array(): void
    {
        $user = User::factory()->create([
            'printable_invoice_fields' => ['logo', 'header'],
        ]);

        $this->assertIsArray($user->printable_invoice_fields);
    }

    public function test_user_uses_soft_deletes(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertNotNull($user->deleted_at);
        $this->assertTrue($user->trashed());
    }

    public function test_user_can_be_restored(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $user->restore();

        $this->assertNull($user->deleted_at);
    }

    public function test_user_has_default_role(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('USER', $user->role);
    }

    public function test_user_has_profile_photo_url_attribute(): void
    {
        $user = User::factory()->create();

        $this->assertArrayHasKey('profile_photo_url', $user->toArray());
    }
}
