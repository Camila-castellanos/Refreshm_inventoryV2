<?php

namespace Tests\Unit\Models;

use App\Models\Item;
use App\Models\Sale;
use App\Models\Shop;
use Carbon\Carbon;
use Tests\TestCaseWithCompany;

class ItemSaleIdFilterTest extends TestCaseWithCompany
{
    public function test_sale_value_excludes_items_with_sale_id(): void
    {
        // Create a reserved item (has sale_id, status != sold)
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $itemWithSaleId = $this->createItem([
            'status' => 'reserved',
            'sale_id' => $sale->id,
            'selling_price' => 500,
            'cost' => 300,
        ]);

        // Create an available item (no sale_id)
        $availableItem = $this->createItem([
            'status' => 'available',
            'sale_id' => null,
            'selling_price' => 200,
            'cost' => 100,
        ]);

        // Query like DashboardController calculateInventoryMetrics
        $inventoryData = Item::where('status', '!=', Item::STATUS_SOLD)
            ->whereIn('type', ['device', 'accessory'])
            ->whereNull('sale_id')
            ->selectRaw('
                COALESCE(SUM(cost), 0) as inventory_value,
                COALESCE(SUM(selling_price), 0) as sale_value
            ')
            ->first();

        // Should only include the available item (200)
        $this->assertEquals(200, $inventoryData->sale_value);
        $this->assertEquals(100, $inventoryData->inventory_value);
    }

    public function test_sale_value_includes_available_items_without_sale_id(): void
    {
        $availableItem = $this->createItem([
            'status' => 'available',
            'sale_id' => null,
            'selling_price' => 150,
            'cost' => 75,
        ]);

        $inventoryData = Item::where('status', '!=', Item::STATUS_SOLD)
            ->whereIn('type', ['device', 'accessory'])
            ->whereNull('sale_id')
            ->selectRaw('
                COALESCE(SUM(cost), 0) as inventory_value,
                COALESCE(SUM(selling_price), 0) as sale_value
            ')
            ->first();

        $this->assertEquals(150, $inventoryData->sale_value);
        $this->assertEquals(75, $inventoryData->inventory_value);
    }

    public function test_sold_items_excluded_from_sale_value_regardless_of_sale_id(): void
    {
        // Create a sold item (status = sold, has sale_id)
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $soldItem = $this->createItem([
            'status' => 'sold',
            'sale_id' => $sale->id,
            'selling_price' => 1000,
            'cost' => 500,
        ]);

        $inventoryData = Item::where('status', '!=', Item::STATUS_SOLD)
            ->whereIn('type', ['device', 'accessory'])
            ->whereNull('sale_id')
            ->selectRaw('
                COALESCE(SUM(cost), 0) as inventory_value,
                COALESCE(SUM(selling_price), 0) as sale_value
            ')
            ->first();

        // Sold item should NOT be included
        $this->assertEquals(0, $inventoryData->sale_value);
        $this->assertEquals(0, $inventoryData->inventory_value);
    }

    public function test_public_inventory_excludes_items_with_sale_id(): void
    {
        // Create a shop
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        // Item with sale_id (reserved) - should be excluded
        $reservedItem = $this->createItem([
            'shop_id' => $shop->id,
            'status' => 'reserved',
            'sale_id' => $sale->id,
            'type' => 'device',
            'model' => 'iPhone 15',
            'manufacturer' => 'Apple',
        ]);

        // Item without sale_id (available) - should be included
        $availableItem = $this->createItem([
            'shop_id' => $shop->id,
            'status' => 'available',
            'sale_id' => null,
            'type' => 'device',
            'model' => 'iPhone 14',
            'manufacturer' => 'Apple',
        ]);

        // Query like PublicStoreInventoryController
        $items = Item::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('status', Item::STATUS_AVAILABLE)
            ->whereNull('sale_id')
            ->whereNull('hold')
            ->whereNotNull('model')
            ->get();

        // Should only have the available item
        $this->assertCount(1, $items);
        $this->assertEquals('iPhone 14', $items->first()->model);
    }

    public function test_public_inventory_includes_available_items_regardless_of_status_if_no_sale_id(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Available item - should be included
        $availableItem = $this->createItem([
            'shop_id' => $shop->id,
            'status' => 'available',
            'sale_id' => null,
            'type' => 'device',
            'model' => 'Samsung Galaxy',
            'manufacturer' => 'Samsung',
        ]);

        $items = Item::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('status', Item::STATUS_AVAILABLE)
            ->whereNull('sale_id')
            ->whereNull('hold')
            ->whereNotNull('model')
            ->get();

        $this->assertCount(1, $items);
        $this->assertEquals('Samsung Galaxy', $items->first()->model);
    }
}