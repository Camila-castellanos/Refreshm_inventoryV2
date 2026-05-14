<?php

namespace Tests\Feature\Controllers;

use App\Models\Item;
use App\Models\Sale;
use App\Models\Storage;
use Tests\TestCaseWithCompany;

class ItemAvailabilityTest extends TestCaseWithCompany
{
    public function test_get_items_only_returns_available_items(): void
    {
        // 1. Available item
        $this->createItem([
            'status' => Item::STATUS_AVAILABLE,
            'model' => 'Available Phone',
        ]);

        $sale = \App\Models\Sale::factory()->create(['user_id' => $this->owner->id]);
        // 2. Reserved item
        $this->createItem([
            'status' => Item::STATUS_RESERVED,
            'sale_id' => $sale->id,
            'model' => 'Reserved Phone',
        ]);

        // 3. Sold item
        $this->createItem([
            'status' => Item::STATUS_SOLD,
            'sold' => now(),
            'model' => 'Sold Phone',
        ]);

        $response = $this->actingAs($this->owner)->getJson('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();
        
        $itemModels = collect($items)->pluck('model')->toArray();
        
        $this->assertContains('Available Phone', $itemModels);
        $this->assertNotContains('Reserved Phone', $itemModels);
        $this->assertNotContains('Sold Phone', $itemModels);
    }

    public function test_build_occupied_map_cleans_up_sold_items_status(): void
    {
        $storage = $this->createStorage();
        $item = $this->createItem([
            'storage_id' => $storage->id,
            'position' => 1,
            'status' => Item::STATUS_SOLD, // Marked as sold but still has position
            'sold' => now(),
        ]);

        // We need to trigger a call to buildOccupiedMapAndCheckConflicts
        // One way is to call ItemController@store or just use Reflection to test the private method
        // But better to test the outcome of an action that uses it.
        
        // Let's try to store a new item in the same position
        $response = $this->actingAs($this->owner)->postJson('/inventory/items', [
            'items' => [
                [
                    'model' => 'New Phone',
                    'storage_id' => $storage->id,
                    'position' => 1,
                    'type' => 'device',
                    'manufacturer' => 'Apple',
                    'colour' => 'Black',
                ]
            ]
        ]);

        // It should NOT have a conflict because the previous item was sold
        // and the cleanup logic should have cleared its position.
        $response->assertStatus(201);
        
        $item->refresh();
        $this->assertNull($item->position, 'Sold item position should have been cleared during conflict check');
        $this->assertNull($item->storage_id);
        $this->assertEquals($storage->id, $item->sold_storage_id);
        $this->assertEquals(1, $item->sold_position);
    }
}
