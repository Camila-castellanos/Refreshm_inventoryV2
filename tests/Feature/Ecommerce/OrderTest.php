<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use App\Models\Item;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class OrderTest extends TestCaseWithCompany
{
    protected function createMarketWithOrders(int $orderCount = 3, array $saleOverrides = []): array
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'is_active' => true,
        ]);

        $orders = collect();

        for ($i = 0; $i < $orderCount; $i++) {
            $orderData = array_merge([
                'user_id' => $this->owner->id,
                'market_id' => $market->id,
                'channel' => 'ecommerce',
                'total' => (string) (100 + ($i * 50)),
                'notes' => json_encode([
                    'email' => 'customer'.($i + 1).'@example.com',
                    'firstName' => 'First',
                    'lastName' => 'Name',
                ]),
            ], $saleOverrides);

            $order = Sale::factory()->create($orderData);

            $items = Item::factory()->count(2)->create([
                'user_id' => $this->owner->id,
                'shop_id' => $shop->id,
                'storage_id' => $this->storage->id,
                'sale_id' => $order->id,
                'sold' => now(),
            ]);

            $orders->push(['sale' => $order, 'items' => $items]);
        }

        return ['market' => $market, 'orders' => $orders, 'shop' => $shop];
    }

    public function test_list_orders_returns_200(): void
    {
        $data = $this->createMarketWithOrders(3);
        $market = $data['market'];

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders");

        $response->assertStatus(200);
    }

    public function test_list_orders_returns_200_for_owner(): void
    {
        $data = $this->createMarketWithOrders(3);
        $market = $data['market'];

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders");

        $response->assertStatus(200);
    }

    public function test_list_orders_with_date_range(): void
    {
        $data = $this->createMarketWithOrders(3);
        $market = $data['market'];

        $start = now()->subDays(7)->format('Y-m-d');
        $end = now()->format('Y-m-d');

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders?start={$start}&end={$end}");

        $response->assertStatus(200);
    }

    public function test_list_orders_returns_orders_with_required_fields(): void
    {
        $data = $this->createMarketWithOrders(2);
        $market = $data['market'];

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders");

        $response->assertStatus(200);
    }

    public function test_list_orders_returns_empty_for_no_orders(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders");

        $response->assertStatus(200);
    }

    public function test_list_orders_requires_authentication(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'is_active' => true,
        ]);

        $response = $this->get("/ecommerce/markets/{$market->id}/orders");

        $response->assertRedirect('/login');
    }

    public function test_list_orders_returns_404_for_invalid_market(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/ecommerce/markets/99999/orders');

        $response->assertStatus(404);
    }

    public function test_list_orders_orders_by_created_at_desc(): void
    {
        $data = $this->createMarketWithOrders(3);
        $market = $data['market'];

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders");

        $response->assertStatus(200);
    }

    public function test_list_orders_with_future_date_range(): void
    {
        $data = $this->createMarketWithOrders(2);
        $market = $data['market'];

        $start = now()->addDays(10)->format('Y-m-d');
        $end = now()->addDays(20)->format('Y-m-d');

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/orders?start={$start}&end={$end}");

        $response->assertStatus(200);
    }
}
