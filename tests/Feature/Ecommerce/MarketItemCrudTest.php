<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use App\Models\Ecommerce\MarketItem;
use App\Models\Item;
use Tests\TestCaseWithCompany;

class MarketItemCrudTest extends TestCaseWithCompany
{
    public function test_market_items_index_returns_items(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);

        MarketItem::factory()->forMarket($market->id)->forItem($item->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/items/{$market->id}");

        $response->assertStatus(200);
    }

    public function test_update_price_changes_custom_price(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/ecommerce/items/{$market->id}/item/{$item->id}/update-price", [
                'price' => 299.99,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('market_items', [
            'market_id' => $market->id,
            'item_id' => $item->id,
            'custom_price' => 299.99,
        ]);
    }

    public function test_update_price_validates_numeric(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/ecommerce/items/{$market->id}/item/{$item->id}/update-price", [
                'price' => 'not-a-number',
            ]);

        $response->assertStatus(302);
    }

    public function test_update_description_changes_description(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/ecommerce/items/{$market->id}/item/{$item->id}/update-description", [
                'description' => '<p>Special deal on this item</p>',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('market_items', [
            'market_id' => $market->id,
            'item_id' => $item->id,
        ]);

        $marketItem = MarketItem::where('market_id', $market->id)->where('item_id', $item->id)->first();
        $this->assertStringContainsString('Special deal', $marketItem->description);
    }

    public function test_toggle_visibility_changes_status(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);

        MarketItem::factory()->forMarket($market->id)->forItem($item->id)->create([
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/ecommerce/items/{$market->id}/item/{$item->id}/toggle-visibility");

        $response->assertStatus(200);

        $marketItem = MarketItem::where('market_id', $market->id)->where('item_id', $item->id)->first();
        $this->assertFalse($marketItem->is_visible);
    }

    public function test_bulk_visibility_update(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();

        $item1 = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);
        $item2 = Item::factory()->forShop($shop)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/ecommerce/items/{$market->id}/set-bulk-visibility", [
                'item_ids' => [$item1->id, $item2->id],
                'is_visible' => true,
            ]);

        $response->assertStatus(200);

        $marketItem1 = MarketItem::where('market_id', $market->id)->where('item_id', $item1->id)->first();
        $marketItem2 = MarketItem::where('market_id', $market->id)->where('item_id', $item2->id)->first();

        $this->assertTrue($marketItem1->is_visible);
        $this->assertTrue($marketItem2->is_visible);
    }

    public function test_bulk_visibility_requires_item_ids(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();

        $response = $this->actingAs($this->owner)
            ->post("/ecommerce/items/{$market->id}/set-bulk-visibility", [
                'item_ids' => [],
                'is_visible' => true,
            ]);

        $response->assertSessionHasErrors(['item_ids']);
    }

    public function test_model_details_returns_variants(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'model' => 'iPhone 13',
            'manufacturer' => 'Apple',
            'type' => 'Phone',
            'user_id' => $this->owner->id,
        ]);

        MarketItem::factory()->forMarket($market->id)->forItem($item->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/items/{$market->id}/model/iPhone%2013/details");

        $response->assertStatus(200);
    }

    public function test_item_by_model_returns_items(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop)->create();
        $item = Item::factory()->forShop($shop)->create([
            'model' => 'iPhone 13',
            'user_id' => $this->owner->id,
        ]);

        MarketItem::factory()->forMarket($market->id)->forItem($item->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/items/{$market->id}/item/{$item->id}/by-model");

        $response->assertStatus(200);
    }
}
