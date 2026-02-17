<?php

namespace Tests\Feature\Inventory;

use App\Models\Item;
use App\Models\Storage;
use Tests\TestCaseWithCompany;

class ItemStorageTest extends TestCaseWithCompany
{
    public function test_assign_single_item_to_storage(): void
    {
        $item = $this->createItem(['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)
            ->post('/items/assign-storage', [
                'items' => [$item->id],
                'storage_id' => $this->storage->id,
            ]);

        $response->assertStatus(200);
        $item->refresh();
        $this->assertEquals($this->storage->id, $item->storage_id);
        $this->assertNotNull($item->position);
    }

    public function test_assign_multiple_items_to_storage(): void
    {
        $items = $this->createItems(3, ['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)
            ->post('/items/assign-storage', [
                'items' => $items->pluck('id')->toArray(),
                'storage_id' => $this->storage->id,
            ]);

        $response->assertStatus(200);
        $items->each(fn ($item) => $item->refresh());
        $positions = $items->pluck('position')->filter()->toArray();
        $this->assertCount(3, array_unique($positions));
    }

    public function test_assign_to_different_storages(): void
    {
        $storage1 = $this->createStorage(['limit' => 10, 'priority' => 1]);
        $storage2 = $this->createStorage(['limit' => 10, 'priority' => 2]);

        $item1 = $this->createItem(['storage_id' => null, 'position' => null]);
        $item2 = $this->createItem(['storage_id' => null, 'position' => null]);

        $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item1->id], 'storage_id' => $storage1->id,
        ]);

        $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item2->id], 'storage_id' => $storage2->id,
        ]);

        $item1->refresh();
        $item2->refresh();

        $this->assertEquals($storage1->id, $item1->storage_id);
        $this->assertEquals($storage2->id, $item2->storage_id);
    }

    public function test_reassign_item_to_different_storage(): void
    {
        $storageA = $this->createStorage(['limit' => 10, 'priority' => 1]);
        $storageB = $this->createStorage(['limit' => 10, 'priority' => 2]);

        $item = $this->createItem(['storage_id' => $storageA->id, 'position' => 1]);

        $response = $this->actingAs($this->owner)
            ->post('/items/assign-storage', [
                'items' => [$item->id], 'storage_id' => $storageB->id,
            ]);

        $response->assertStatus(200);
        $item->refresh();
        $this->assertEquals($storageB->id, $item->storage_id);
    }

    public function test_storage_respects_limit_capacity(): void
    {
        $limitedStorage = $this->createStorage(['limit' => 2, 'priority' => 1]);

        $item1 = $this->createItem(['storage_id' => null, 'position' => null]);
        $item2 = $this->createItem(['storage_id' => null, 'position' => null]);
        $item3 = $this->createItem(['storage_id' => null, 'position' => null]);

        $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item1->id], 'storage_id' => $limitedStorage->id,
        ]);

        $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item2->id], 'storage_id' => $limitedStorage->id,
        ]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item3->id], 'storage_id' => $limitedStorage->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_storage_full_returns_error_message(): void
    {
        $limitedStorage = $this->createStorage(['limit' => 1, 'priority' => 1]);

        $item1 = $this->createItem(['storage_id' => null, 'position' => null]);
        $item2 = $this->createItem(['storage_id' => null, 'position' => null]);

        $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item1->id], 'storage_id' => $limitedStorage->id,
        ]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item2->id], 'storage_id' => $limitedStorage->id,
        ]);

        $response->assertStatus(400);
        $data = $response->json();
        $this->assertArrayHasKey('message', $data);
    }

    public function test_available_slots_calculation(): void
    {
        $storage = $this->createStorage(['limit' => 10, 'priority' => 1]);

        Item::factory()->count(3)->create(['storage_id' => $storage->id]);
        Item::factory()->count(2)->create(['storage_id' => $storage->id]);

        $availableSlots = Storage::getAvailableSlots($storage->id);
        $this->assertEquals(5, $availableSlots);
    }

    public function test_auto_assigns_next_available_position(): void
    {
        $storage = $this->createStorage(['limit' => 10, 'priority' => 1]);

        Item::factory()->create(['storage_id' => $storage->id, 'position' => 1]);
        Item::factory()->create(['storage_id' => $storage->id, 'position' => 3]);

        $newItem = $this->createItem(['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$newItem->id], 'storage_id' => $storage->id,
        ]);

        $response->assertStatus(200);
        $newItem->refresh();
        $this->assertEquals(2, $newItem->position);
    }

    public function test_prevents_duplicate_positions(): void
    {
        $storage = $this->createStorage(['limit' => 10, 'priority' => 1]);
        Item::factory()->create(['storage_id' => $storage->id, 'position' => 1]);

        $item2 = $this->createItem(['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item2->id], 'storage_id' => $storage->id,
        ]);

        $response->assertStatus(200);
        $item2->refresh();
        $this->assertNotEquals(1, $item2->position);
    }

    public function test_position_conflict_resolution(): void
    {
        $storage = $this->createStorage(['limit' => 10, 'priority' => 1]);
        Item::factory()->create(['storage_id' => $storage->id, 'position' => 1]);
        Item::factory()->create(['storage_id' => $storage->id, 'position' => 2]);

        $item3 = $this->createItem(['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item3->id], 'storage_id' => $storage->id,
        ]);

        $response->assertStatus(200);
        $item3->refresh();
        $this->assertNotNull($item3->position);
    }

    public function test_sold_items_dont_block_position_for_new_items(): void
    {
        $storage = $this->createStorage(['limit' => 10, 'priority' => 1]);

        $soldItem = Item::factory()->create([
            'storage_id' => $storage->id,
            'position' => 1,
            'sold' => now(),
        ]);

        $newItem = $this->createItem(['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$newItem->id], 'storage_id' => $storage->id,
        ]);

        $response->assertStatus(200);
        $newItem->refresh();
        $this->assertNotNull($newItem->position);
    }

    public function test_assign_to_invalid_storage_fails(): void
    {
        $item = $this->createItem(['storage_id' => null, 'position' => null]);

        $response = $this->actingAs($this->owner)->post('/items/assign-storage', [
            'items' => [$item->id], 'storage_id' => 99999,
        ]);

        $response->assertStatus(302);
    }

    public function test_position_null_for_unassigned_items(): void
    {
        $item = $this->createItem(['storage_id' => null, 'position' => null]);

        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }

    public function test_get_items_includes_storage_info(): void
    {
        $item = $this->createItem(['storage_id' => $this->storage->id, 'position' => 5]);

        $response = $this->actingAs($this->owner)->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();
        $foundItem = collect($items)->firstWhere('id', $item->id);

        $this->assertNotNull($foundItem);
        $this->assertEquals($this->storage->id, $foundItem['storage_id']);
    }
}
