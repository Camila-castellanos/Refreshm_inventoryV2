<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class CheckoutTest extends TestCaseWithCompany
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
        ], $itemOverrides));

        return ['market' => $market, 'items' => $items, 'shop' => $shop];
    }

    public function test_create_payment_intent_returns_200(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'currency' => 'usd',
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'clientSecret',
                'paymentIntentId',
            ]);
    }

    public function test_create_payment_intent_validates_amount_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
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

    public function test_create_payment_intent_validates_items_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_create_payment_intent_validates_customer_email(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'not-an-email',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_create_payment_intent_returns_400_when_items_unavailable(): void
    {
        $data = $this->createMarketWithItems([
            'sold' => now(),
        ]);

        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'unavailable_items',
            ]);
    }

    public function test_create_payment_intent_returns_404_for_invalid_market(): void
    {
        $response = $this->postJson('/market/invalid-market/checkout/intent', [
            'amount' => 299.99,
            'items' => [['id' => 1]],
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(404);
    }

    public function test_create_payment_intent_validates_items_min_one(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
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

    public function test_create_payment_intent_validates_items_have_id(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => [['no_id' => 1]],
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_finalize_order_validates_payment_intent_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/finalize", [
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
            'subtotal' => 250.00,
            'total' => 250.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_finalize_order_validates_items_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->postJson("/market/{$market->slug}/checkout/finalize", [
            'paymentIntentId' => 'pi_test_123',
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
            'subtotal' => 250.00,
            'total' => 250.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_finalize_order_validates_total_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/finalize", [
            'paymentIntentId' => 'pi_test_123',
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
            'subtotal' => 250.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_finalize_order_validates_customer_email(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/finalize", [
            'paymentIntentId' => 'pi_test_123',
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'invalid-email',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
            'subtotal' => 250.00,
            'total' => 250.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_get_stripe_key_returns_publishable_key(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->getJson("/market/{$market->slug}/checkout/stripe-key");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'publishableKey',
            ]);
    }

    public function test_get_stripe_key_does_not_require_market(): void
    {
        $response = $this->getJson('/market/nonexistent-market/checkout/stripe-key');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'publishableKey',
            ]);
    }

    public function test_checkout_validates_customer_first_name_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => '',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_validates_customer_last_name_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => '',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_validates_customer_phone_required(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_validates_items_array(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => 'not-an-array',
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_validates_amount_min_1(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 0.5,
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

    public function test_checkout_market_uses_default_currency(): void
    {
        $shop = $this->createShop();

        $market = Market::factory()->forShop($shop)->create([
            'slug' => 'market-cad-currency',
            'is_active' => true,
            'currency' => 'CAD',
        ]);

        $items = Item::factory()->count(2)->create([
            'user_id' => $this->owner->id,
            'shop_id' => $shop->id,
            'storage_id' => $this->storage->id,
        ]);

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_checkout_items_can_have_notes(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];
        $items = $data['items'];

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 299.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
                'notes' => 'Please gift wrap',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_checkout_with_multiple_items(): void
    {
        $data = $this->createMarketWithItems();
        $market = $data['market'];

        $items = Item::factory()->count(5)->create([
            'user_id' => $this->owner->id,
            'shop_id' => $data['shop']->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
        ]);

        $response = $this->postJson("/market/{$market->slug}/checkout/intent", [
            'amount' => 999.99,
            'items' => $items->map(fn ($item) => ['id' => $item->id])->toArray(),
            'customer' => [
                'email' => 'test@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '1234567890',
            ],
        ]);

        $response->assertStatus(200);
    }
}
