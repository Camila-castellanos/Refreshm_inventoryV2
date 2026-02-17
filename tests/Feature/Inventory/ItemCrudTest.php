<?php

namespace Tests\Feature\Inventory;

use App\Models\Item;
use App\Models\TabItem;
use Tests\TestCaseWithCompany;

class ItemCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_inventory_items(): void
    {
        $this->createItem();
        $this->createItem(['model' => 'iPhone 15 Pro']);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items');

        $response->assertStatus(200);
    }

    public function test_get_items_returns_json(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem(['model' => 'Samsung Galaxy S24']);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $items = $response->json();
        $this->assertIsArray($items);
    }

    public function test_index_excludes_sold_items(): void
    {
        $this->createItem();
        $soldItem = $this->createItem(['sold' => now()]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();

        $itemIds = collect($items)->pluck('id')->toArray();
        $this->assertNotContains($soldItem->id, $itemIds);
    }

    public function test_index_excludes_hold_items(): void
    {
        $this->createItem();
        $holdItem = $this->createItem(['hold' => now(), 'customer' => 'Test Customer']);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();

        $itemIds = collect($items)->pluck('id')->toArray();
        $this->assertNotContains($holdItem->id, $itemIds);
    }

    public function test_can_store_single_item(): void
    {
        $itemData = [
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'colour' => 'Black',
                    'grade' => 'A',
                    'battery' => '90%',
                    'cost' => 500.00,
                    'selling_price' => 699.99,
                    'storage_id' => $this->storage->id,
                    'imei' => '123456789012345',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', $itemData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('items', [
            'model' => 'iPhone 15',
            'manufacturer' => 'Apple',
            'imei' => '123456789012345',
        ]);
    }

    public function test_can_store_multiple_items(): void
    {
        $itemData = [
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'colour' => 'Black',
                    'grade' => 'A',
                    'battery' => '90%',
                    'cost' => 500.00,
                    'selling_price' => 699.99,
                    'storage_id' => $this->storage->id,
                    'imei' => '123456789012345',
                ],
                [
                    'manufacturer' => 'Samsung',
                    'model' => 'Galaxy S24',
                    'colour' => 'White',
                    'grade' => 'B',
                    'battery' => '85%',
                    'cost' => 450.00,
                    'selling_price' => 599.99,
                    'storage_id' => $this->storage->id,
                    'imei' => '123456789012346',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', $itemData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('items', ['model' => 'iPhone 15']);
        $this->assertDatabaseHas('items', ['model' => 'Galaxy S24']);
    }

    public function test_update_endpoint_accepts_data_without_validation(): void
    {
        // Note: The update endpoint doesn't enforce validation via ItemForm
        // This test verifies the endpoint accepts data without storage_id
        $itemData = [
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', $itemData);

        // The endpoint accepts this without validation
        $response->assertStatus(200);
    }

    public function test_can_update_item(): void
    {
        $item = $this->createItem([
            'model' => 'iPhone 14',
            'selling_price' => 599.99,
        ]);

        $updateData = [
            'items' => [
                [
                    'id' => $item->id,
                    'model' => 'iPhone 14 Pro',
                    'selling_price' => 799.99,
                    'storage_id' => $this->storage->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', $updateData);

        $response->assertStatus(200);
        $item->refresh();
        $this->assertEquals('iPhone 14 Pro', $item->model);
        $this->assertEquals(799.99, $item->selling_price);
    }

    public function test_can_delete_item(): void
    {
        $item = $this->createItem();

        $response = $this->actingAs($this->owner)
            ->delete('/inventory/items/obliterate', [
                0 => ['id' => $item->id],
            ]);

        $response->assertStatus(200);
    }

    public function test_can_delete_multiple_items(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();
        $item3 = $this->createItem();

        $response = $this->actingAs($this->owner)
            ->delete('/inventory/items/obliterate', [
                0 => ['id' => $item1->id],
                1 => ['id' => $item2->id],
                2 => ['id' => $item3->id],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('items', ['id' => $item2->id]);
        $this->assertDatabaseMissing('items', ['id' => $item3->id]);
    }

    public function test_delete_removes_tab_items(): void
    {
        $item = $this->createItem();
        $tab = $this->createTab();

        TabItem::create([
            'item_id' => $item->id,
            'tab_id' => $tab->id,
        ]);

        $this->assertDatabaseHas('tab_items', [
            'item_id' => $item->id,
            'tab_id' => $tab->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete('/inventory/items/obliterate', [
                0 => ['id' => $item->id],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tab_items', [
            'item_id' => $item->id,
        ]);
    }

    public function test_search_returns_items_by_model(): void
    {
        $this->createItem(['model' => 'iPhone 15 Pro Max']);
        $this->createItem(['model' => 'Samsung Galaxy S24']);
        $this->createItem(['model' => 'Google Pixel 8']);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/search?q=iPhone');

        $response->assertStatus(200);
        $items = $response->json();
        $this->assertNotEmpty($items);
    }

    public function test_search_returns_items_by_imei(): void
    {
        $item = $this->createItem(['imei' => '123456789012999']);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/search?q=123456789012999');

        $response->assertStatus(200);
        $items = $response->json();
        $this->assertNotEmpty($items);
        $this->assertEquals($item->id, $items[0]['id']);
    }

    public function test_get_unique_models_by_manufacturer(): void
    {
        $this->createItem(['manufacturer' => 'Apple', 'model' => 'iPhone 15 128GB']);
        $this->createItem(['manufacturer' => 'Apple', 'model' => 'iPhone 15 256GB']);
        $this->createItem(['manufacturer' => 'Apple', 'model' => 'iPhone 14']);
        $this->createItem(['manufacturer' => 'Samsung', 'model' => 'Galaxy S24']);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/get_unique_models_by_manufacturer', [
                'manufacturers' => ['Apple'],
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('models', $data);
    }

    public function test_get_specific_items_by_ids(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/get-specific-items', [
                'ids' => [$item1->id, $item2->id],
            ]);

        $response->assertStatus(200);
        $items = $response->json();
        $this->assertCount(2, $items);
    }

    public function test_requires_authentication_for_index(): void
    {
        $response = $this->get('/inventory/items');

        $response->assertRedirect('/login');
    }

    public function test_requires_authentication_for_get_items(): void
    {
        $response = $this->get('/inventory/items/getItems');

        $response->assertRedirect('/login');
    }

    public function test_item_auto_assigns_position(): void
    {
        $itemData = [
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'colour' => 'Black',
                    'grade' => 'A',
                    'cost' => 500.00,
                    'selling_price' => 699.99,
                    'storage_id' => $this->storage->id,
                    'imei' => '123456789012347',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', $itemData);

        $response->assertStatus(200);
        $item = Item::where('imei', '123456789012347')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->position);
    }

    public function test_list_api_returns_available_items(): void
    {
        $this->createItem();
        $this->createItem(['model' => 'Test Item']);
        $soldItem = $this->createItem(['sold' => now()]);

        $response = $this->actingAs($this->owner)
            ->get('/api/items');

        $response->assertStatus(200);
        $items = $response->json();
        $itemIds = collect($items)->pluck('id')->toArray();
        $this->assertNotContains($soldItem->id, $itemIds);
    }
}
