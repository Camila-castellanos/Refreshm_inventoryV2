<?php

namespace Tests\Feature\Sales;

use App\Models\Item;
use App\Models\ReturnItems;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class SalesReturnTest extends TestCaseWithCompany
{
    // === EXISTING TESTS ===

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

    // === RETURN ITEMS MODEL TESTS ===

    public function test_return_items_fillable_attributes(): void
    {
        $returnItem = ReturnItems::create([
            'item' => '1',
            'customer' => '1',
            'credit' => 100.00,
            'imei' => '123456789012345',
            'model' => 'iPhone 15',
            'sale' => '1',
        ]);

        $this->assertDatabaseHas('return_items', [
            'item' => '1',
            'credit' => 100.00,
        ]);
    }

    public function test_return_items_has_credit_default(): void
    {
        $returnItem = ReturnItems::create([
            'item' => '1',
            'customer' => '1',
            'imei' => '123456789012345',
            'model' => 'iPhone 15',
        ]);

        $this->assertEquals(0, $returnItem->credit);
    }

    public function test_return_items_requested_default(): void
    {
        $returnItem = ReturnItems::create([
            'item' => '1',
            'customer' => '1',
            'imei' => '123456789012345',
            'model' => 'iPhone 15',
        ]);

        $this->assertNull($returnItem->requested);
    }

    public function test_return_items_factory_valid(): void
    {
        $returnItem = \Database\Factories\ReturnItemFactory::new()->create();

        $this->assertNotNull($returnItem->item);
        $this->assertNotNull($returnItem->customer);
        $this->assertNotNull($returnItem->imei);
        $this->assertNotNull($returnItem->model);
    }

    // === RETURN INTEGRATION TESTS ===

    public function test_return_updates_item_sale_reference(): void
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

        $this->assertNotNull($item->sale_id);

        $this->actingAs($this->owner)->put('/inventory/items/return', [
            'data' => [['id' => $item->id]],
        ]);

        $item->refresh();
        $this->assertNull($item->sale_id);
    }

    public function test_return_without_items_returns_error(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/inventory/items/return', []);

        $response->assertStatus(400);
    }

    public function test_return_multiple_items(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 300.00,
        ]);

        $item1 = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 100.00,
        ]);

        $item2 = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 200.00,
        ]);

        $response = $this->actingAs($this->owner)->put('/inventory/items/return', [
            'data' => [
                ['id' => $item1->id],
                ['id' => $item2->id],
            ],
        ]);

        $response->assertStatus(200);

        $item1->refresh();
        $item2->refresh();

        $this->assertNull($item1->sold);
        $this->assertNull($item2->sold);
    }

    // === REFUND TESTS ===

    public function test_refund_removes_sale_reference(): void
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

        $this->actingAs($this->owner)->put('/inventory/items/refund', [
            'data' => [['id' => $item->id]],
        ]);

        $item->refresh();
        $this->assertNull($item->sale_id);
    }

    public function test_refund_endpoint_with_valid_item(): void
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

        $response = $this->actingAs($this->owner)->put('/inventory/items/refund', [
            'data' => [['id' => $item->id]],
        ]);

        $response->assertStatus(200);
    }

    // === EDGE CASES ===

    public function test_return_with_zero_price_item(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 0,
        ]);

        $item = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 0,
        ]);

        $response = $this->actingAs($this->owner)->put('/inventory/items/return', [
            'data' => [['id' => $item->id]],
        ]);

        $response->assertStatus(200);
    }

    public function test_return_partial_sale_adjustment(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'subtotal' => 200.00,
            'total' => 226.00,
            'tax' => 13.00,
            'flatTax' => 26.00,
            'balance_remaining' => 226.00,
        ]);

        $item = Item::withoutGlobalScopes()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 100.00,
        ]);

        $this->actingAs($this->owner)->put('/inventory/items/return', [
            'data' => [['id' => $item->id]],
        ]);

        $sale->refresh();
        $this->assertEquals(200.00 - 100.00, $sale->subtotal);
    }
}
