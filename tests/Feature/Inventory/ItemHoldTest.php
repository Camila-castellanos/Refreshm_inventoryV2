<?php

namespace Tests\Feature\Inventory;

use Tests\TestCaseWithCompany;

class ItemHoldTest extends TestCaseWithCompany
{
    public function test_can_put_single_item_on_hold(): void
    {
        $item = $this->createItem();

        $response = $this->actingAs($this->owner)
            ->put('/inventory/items/hold', [
                'data' => [['id' => $item->id]],
                'customer' => 'John Doe',
            ]);

        $response->assertStatus(200);
        $item->refresh();
        $this->assertNotNull($item->hold);
        $this->assertEquals('John Doe', $item->customer);
    }

    public function test_can_put_multiple_items_on_hold(): void
    {
        $items = $this->createItems(3);

        $response = $this->actingAs($this->owner)
            ->put('/inventory/items/hold', [
                'data' => $items->map(fn ($i) => ['id' => $i->id])->toArray(),
                'customer' => 'Jane Smith',
            ]);

        $response->assertStatus(200);
        $items->each(fn ($item) => $item->refresh());

        foreach ($items as $item) {
            $this->assertNotNull($item->hold);
            $this->assertEquals('Jane Smith', $item->customer);
        }
    }

    public function test_can_view_items_on_hold(): void
    {
        $item = $this->createItem();

        $this->actingAs($this->owner)->put('/inventory/items/hold', [
            'data' => [['id' => $item->id]],
            'customer' => 'Hold Customer',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/hold');

        $response->assertStatus(200);
    }

    public function test_can_unhold_item(): void
    {
        $item = $this->createItem();

        $this->actingAs($this->owner)->put('/inventory/items/hold', [
            'data' => [['id' => $item->id]],
            'customer' => 'Hold Customer',
        ]);

        $item->refresh();
        $this->assertNotNull($item->hold);

        $response = $this->actingAs($this->owner)
            ->put('/inventory/items/unhold', [
                'data' => [['id' => $item->id]],
            ]);

        $response->assertStatus(200);
        $item->refresh();
        $this->assertNull($item->hold);
    }

    public function test_unhold_restores_item_to_inventory(): void
    {
        $item = $this->createItem();

        $this->actingAs($this->owner)->put('/inventory/items/hold', [
            'data' => [['id' => $item->id]],
            'customer' => 'Hold Customer',
        ]);

        $this->actingAs($this->owner)->put('/inventory/items/unhold', [
            'data' => [['id' => $item->id]],
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();
        $itemIds = collect($items)->pluck('id')->toArray();

        $this->assertContains($item->id, $itemIds);
    }

    public function test_can_unhold_multiple_items(): void
    {
        $items = $this->createItems(3);

        $this->actingAs($this->owner)->put('/inventory/items/hold', [
            'data' => $items->map(fn ($i) => ['id' => $i->id])->toArray(),
            'customer' => 'Multi Hold',
        ]);

        $this->actingAs($this->owner)->put('/inventory/items/unhold', [
            'data' => $items->map(fn ($i) => ['id' => $i->id])->toArray(),
        ]);

        $items->each(fn ($item) => $item->refresh());

        foreach ($items as $item) {
            $this->assertNull($item->hold);
        }
    }

    public function test_hold_preserves_item_data(): void
    {
        $item = $this->createItem([
            'model' => 'iPhone 15 Pro',
            'selling_price' => 999.99,
            'imei' => '123456789012345',
        ]);

        $this->actingAs($this->owner)->put('/inventory/items/hold', [
            'data' => [['id' => $item->id]],
            'customer' => 'Preserve Test',
        ]);

        $item->refresh();

        $this->assertEquals('iPhone 15 Pro', $item->model);
        $this->assertEquals(999.99, $item->selling_price);
        $this->assertEquals('123456789012345', $item->imei);
    }

    public function test_hold_requires_authentication(): void
    {
        $item = $this->createItem();

        $response = $this->put('/inventory/items/hold', [
            'data' => [['id' => $item->id]],
            'customer' => 'No Auth',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_unhold_requires_authentication(): void
    {
        $item = $this->createItem();

        $response = $this->put('/inventory/items/unhold', [
            'data' => [['id' => $item->id]],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_view_hold_requires_authentication(): void
    {
        $response = $this->get('/inventory/items/hold');

        $response->assertRedirect('/login');
    }
}
