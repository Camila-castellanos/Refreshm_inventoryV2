<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use Tests\TestCaseWithCompany;

class StoreCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_stores_for_owner(): void
    {
        Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/stores');

        $response->assertStatus(200);
    }

    public function test_create_returns_create_page(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/stores/create');

        $response->assertStatus(200);
    }

    public function test_store_creates_store(): void
    {
        $data = [
            'name' => 'Test Store',
            'address' => '123 Test Street',
            'header' => 'Test Header',
            'footer' => 'Test Footer',
            'price_percent' => 10.00,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/stores', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('stores', [
            'name' => 'Test Store',
            'address' => '123 Test Street',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/stores', []);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_edit_returns_edit_page(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get("/stores/{$store->id}/edit");

        $response->assertStatus(200);
    }

    public function test_update_modifies_store(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create([
            'name' => 'Original Name',
            'price_percent' => 10,
        ]);

        $data = [
            'name' => 'Updated Name',
            'address' => '456 New Street',
            'header' => 'New Header',
            'footer' => 'New Footer',
            'email' => $this->owner->email,
        ];

        $response = $this->actingAs($this->owner)
            ->put("/stores/{$store->id}", $data);

        $response->assertStatus(200);

        $store->refresh();
        $this->assertEquals('Updated Name', $store->name);
        $this->assertEquals('456 New Street', $store->address);
    }

    public function test_destroy_deletes_store(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->delete("/stores/{$store->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('stores', ['id' => $store->id]);
    }

    public function test_update_store_percent(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create([
            'price_percent' => 10.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/stores/{$store->id}/cut", [
                'store_percent' => 25.00,
            ]);

        $response->assertStatus(200);

        $store->refresh();
        $this->assertEquals(25.00, $store->price_percent);
    }

    public function test_store_belongs_to_user(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $this->assertEquals($this->owner->id, $store->user_id);
    }
}
