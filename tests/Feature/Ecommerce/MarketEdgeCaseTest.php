<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class MarketEdgeCaseTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    protected function createMarketWithItems(array $itemOverrides = []): array
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'test-market',
            'is_active' => true,
            'currency' => 'USD',
        ]);

        $items = Item::factory()->count(3)->create(array_merge([
            'user_id' => $this->owner->id,
            'shop_id' => $shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'hold' => null,
        ], $itemOverrides));

        return ['market' => $market, 'items' => $items, 'shop' => $shop];
    }

    public function test_market_inactive_does_not_show_items(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'inactive-market',
            'is_active' => false,
        ]);

        Item::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'shop_id' => $shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(404);
    }

    public function test_market_with_sold_items_not_shown(): void
    {
        $data = $this->createMarketWithItems([
            'sold' => now(),
        ]);

        $market = $data['market'];

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
    }

    public function test_market_with_held_items_not_shown(): void
    {
        $data = $this->createMarketWithItems([
            'hold' => now(),
            'customer' => 'Test Customer',
        ]);

        $market = $data['market'];

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
    }

    public function test_market_without_items(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'empty-market',
            'is_active' => true,
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
    }

    public function test_market_with_custom_domain(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'custom-domain-market',
            'is_active' => true,
            'custom_domain' => 'shop.example.com',
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
    }

    public function test_checkout_with_zero_price_item(): void
    {
        $data = $this->createMarketWithItems([
            'selling_price' => 0,
        ]);

        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 0,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_with_invalid_customer_email(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'not-valid-email',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_with_empty_customer(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_market_with_different_currencies(): void
    {
        $shop = $this->createShop();

        $currencies = ['USD', 'CAD', 'EUR', 'GBP'];

        foreach ($currencies as $currency) {
            $market = Market::factory()->forShop($shop)->create([
                'slug' => 'market-'.strtolower($currency),
                'is_active' => true,
                'currency' => $currency,
            ]);

            $response = $this->get("/market/{$market->slug}");
            $response->assertStatus(200);
        }
    }

    public function test_checkout_with_empty_items_array(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 0,
            'items' => [],
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_market_show_inventory_count_setting(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'market-count',
            'is_active' => true,
            'show_inventory_count' => true,
        ]);

        Item::factory()->count(5)->create([
            'user_id' => $this->owner->id,
            'shop_id' => $shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
    }

    public function test_market_with_null_currency(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'market-cad-currency',
            'is_active' => true,
            'currency' => 'CAD',
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
    }
}
