<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\User;
use Tests\TestCaseWithCompany;

class StoreEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_store_without_name_fails(): void
    {
        $data = [
            'address' => '123 Test Street',
            'price_percent' => 10,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_store_with_name_too_long_fails_at_db_level(): void
    {
        $longName = str_repeat('A', 256);

        $data = [
            'name' => $longName,
            'address' => '123 Test Street',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(500);
    }

    public function test_create_store_without_address_fails(): void
    {
        $data = [
            'name' => 'Test Store',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertSessionHasErrors('address');
    }

    public function test_create_store_with_negative_price_percent_fails_at_db(): void
    {
        $data = [
            'name' => 'Test Store',
            'address' => '123 Test Street',
            'price_percent' => -10,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(500);
    }

    public function test_create_store_with_price_percent_over_100_fails_at_db(): void
    {
        $data = [
            'name' => 'Test Store',
            'address' => '123 Test Street',
            'price_percent' => 150,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(500);
    }

    public function test_update_nonexistent_store_returns_404(): void
    {
        $data = [
            'name' => 'Updated Store',
            'email' => $this->owner->email,
        ];

        $response = $this->actingAs($this->owner)
            ->put('/stores/99999', $data);

        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_store_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete('/stores/99999');

        $response->assertStatus(404);
    }

    public function test_assign_nonexistent_users_to_store_returns_500(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/users", [
                'users' => [
                    ['id' => 99999],
                ],
            ]);

        $response->assertStatus(500);
    }

    public function test_assign_users_to_store_successfully(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $user1 = User::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => null,
        ]);
        $user2 = User::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => null,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/users", [
                'users' => [
                    ['id' => $user1->id],
                    ['id' => $user2->id],
                ],
            ]);

        $response->assertStatus(200);

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals($store->id, $user1->store_id);
        $this->assertEquals($store->id, $user2->store_id);
    }

    public function test_update_store_without_data_fails(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->put("/stores/{$store->id}", []);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_store_with_header_too_long_fails_at_db(): void
    {
        $longHeader = str_repeat('H', 500);

        $data = [
            'name' => 'Test Store',
            'address' => '123 Test Street',
            'header' => $longHeader,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(500);
    }

    public function test_receipt_detail_of_nonexistent_store_returns_200(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/receipt/detail/99999');

        $response->assertStatus(200);
    }

    public function test_list_users_from_store_without_users(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get("/stores/{$store->id}/users");

        $response->assertStatus(200);
    }

    public function test_price_percent_with_negative_decimal_allows(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->put("/stores/{$store->id}/cut", [
                'store_percent' => -5.5,
            ]);

        $response->assertStatus(200);
    }

    public function test_index_with_filter_all_as_user_returns_403(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        $response = $this->actingAs($user)
            ->get('/stores?filter=all');

        $response->assertStatus(403);
    }

    public function test_create_store_with_max_length_name(): void
    {
        $maxName = str_repeat('A', 255);

        $data = [
            'name' => $maxName,
            'address' => '123 Test Street',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(500);
    }

    public function test_assign_users_clears_previous_store_assignments(): void
    {
        $store1 = Store::factory()->forOwner($this->owner)->create();
        $store2 = Store::factory()->forOwner($this->owner)->create();

        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $store1->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store2->id}/users", [
                'users' => [
                    ['id' => $user->id],
                ],
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals($store2->id, $user->store_id);
    }

    public function test_update_store_percent_with_zero(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create([
            'price_percent' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/stores/{$store->id}/cut", [
                'store_percent' => 0,
            ]);

        $response->assertStatus(200);

        $store->refresh();
        $this->assertEquals(0, $store->price_percent);
    }

    public function test_create_store_with_footer_too_long_fails_at_db(): void
    {
        $longFooter = str_repeat('F', 500);

        $data = [
            'name' => 'Test Store',
            'address' => '123 Test Street',
            'footer' => $longFooter,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(500);
    }
}
