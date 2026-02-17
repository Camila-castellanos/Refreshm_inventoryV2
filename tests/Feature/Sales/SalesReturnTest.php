<?php

namespace Tests\Feature\Sales;

use App\Models\Item;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class SalesReturnTest extends TestCaseWithCompany
{
    public function test_can_return_item(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $item = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/inventory/items/return', [
                'data' => [['id' => $item->id]],
            ]);

        $response->assertStatus(200);

        $item->refresh();

        $this->assertNull($item->sold);
    }

    public function test_return_restores_item_to_inventory(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $item = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $this->actingAs($this->owner)->put('/inventory/items/return', [
            'data' => [['id' => $item->id]],
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();
        $itemIds = collect($items)->pluck('id')->toArray();

        $this->assertContains($item->id, $itemIds);
    }

    public function test_can_refund_item(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $item = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/inventory/items/refund', [
                'data' => [['id' => $item->id]],
            ]);

        $response->assertStatus(200);

        $item->refresh();

        $this->assertNull($item->sold);
    }

    public function test_return_requires_authentication(): void
    {
        $response = $this->put('/inventory/items/return', [
            'data' => [['id' => 1]],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_refund_requires_authentication(): void
    {
        $response = $this->put('/inventory/items/refund', [
            'data' => [['id' => 1]],
        ]);

        $response->assertRedirect('/login');
    }
}
