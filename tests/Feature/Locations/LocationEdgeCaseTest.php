<?php

namespace Tests\Feature\Locations;

use App\Models\Item;
use App\Models\Location;
use App\Models\Store;
use App\Models\User;
use Tests\TestCaseWithCompany;

class LocationEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_location_without_name_fails(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/locations", [
                'name' => '',
                'address' => '123 Test St',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_location_with_duplicate_name_in_same_store_allows(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $existingLocation = Location::factory()->create([
            'name' => 'Duplicate Location',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/locations", [
                'name' => 'Duplicate Location',
                'address' => '456 Test Ave',
            ]);

        $response->assertStatus(201);
    }

    public function test_create_location_with_name_too_long_allows(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/locations", [
                'name' => $longName,
            ]);

        $response->assertStatus(201);
    }

    public function test_create_location_with_max_length_name_succeeds(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $maxLengthName = str_repeat('A', 255);

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/locations", [
                'name' => $maxLengthName,
            ]);

        $response->assertStatus(201);
    }

    public function test_create_location_without_store_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/stores//locations', [
                'name' => 'Test Location',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_location_with_empty_name_fails(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/locations/{$location->id}", [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_location_maintains_store_association(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/locations/{$location->id}", [
                'name' => 'Updated Name',
                'address' => 'New Address',
            ]);

        $response->assertStatus(200);

        $location->refresh();
        $this->assertEquals('Updated Name', $location->name);
        $this->assertEquals($store->id, $location->store_id);
    }

    public function test_delete_location_with_users_associated(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $user = User::factory()->create([
            'store_id' => $store->id,
            'company_id' => $this->owner->company_id,
            'location_id' => $location->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/locations/{$location->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_delete_location_with_items_associated(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'storage_id' => $location->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/locations/{$location->id}");

        $response->assertStatus(200);
    }

    public function test_delete_nonexistent_location_returns_error(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete('/locations/99999');

        $response->assertStatus(404);
    }

    public function test_assign_users_to_location_with_nonexistent_users_returns_500(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/locations/{$location->id}/users", [
                'users' => [
                    ['id' => 99999],
                ],
            ]);

        $response->assertStatus(500);
    }

    public function test_assign_users_to_location_successfully(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $user1 = User::factory()->create([
            'store_id' => $store->id,
            'company_id' => $this->owner->company_id,
            'location_id' => null,
        ]);

        $user2 = User::factory()->create([
            'store_id' => $store->id,
            'company_id' => $this->owner->company_id,
            'location_id' => null,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/locations/{$location->id}/users", [
                'users' => [
                    ['id' => $user1->id],
                    ['id' => $user2->id],
                ],
            ]);

        $response->assertStatus(200);

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals($location->id, $user1->location_id);
        $this->assertEquals($location->id, $user2->location_id);
    }

    public function test_get_users_from_empty_location(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get("/locations/{$location->id}/users");

        $response->assertStatus(200);
    }

    public function test_create_location_with_very_long_address_allows(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $longAddress = str_repeat('A', 500);

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/locations", [
                'name' => 'Test Location',
                'address' => $longAddress,
            ]);

        $response->assertStatus(201);
    }

    public function test_update_only_address_without_changing_name(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();
        $location = Location::factory()->create([
            'store_id' => $store->id,
            'name' => 'Test Location',
            'address' => 'Original Address',
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/locations/{$location->id}", [
                'name' => 'Test Location',
                'address' => 'Updated Address',
            ]);

        $response->assertStatus(200);

        $location->refresh();
        $this->assertEquals('Updated Address', $location->address);
    }

    public function test_create_location_with_minimal_data(): void
    {
        $store = Store::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post("/stores/{$store->id}/locations", [
                'name' => 'Minimal Location',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('locations', [
            'name' => 'Minimal Location',
            'store_id' => $store->id,
        ]);
    }
}
