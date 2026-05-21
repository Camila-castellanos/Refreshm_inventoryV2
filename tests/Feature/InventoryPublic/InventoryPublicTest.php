<?php

namespace Tests\Feature\InventoryPublic;

use Tests\TestCaseWithCompany;

class InventoryPublicTest extends TestCaseWithCompany
{
    public function test_index_returns_404_for_invalid_shop(): void
    {
        $response = $this->get('/public-store/invalid-shop-12345');

        $response->assertStatus(404);
    }

    public function test_index_returns_items_for_valid_shop_by_slug(): void
    {
        $shop = $this->createShop();

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'iPhone 15',
            'manufacturer' => 'Apple',
        ]);

        $response = $this->get('/public-store/'.$shop->slug);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('shopName', $shop->name)
            ->where('shopSlug', $shop->slug)
        );
    }

    public function test_index_returns_items_for_valid_shop_by_id(): void
    {
        $shop = $this->createShop();

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'Galaxy S24',
            'manufacturer' => 'Samsung',
        ]);

        $response = $this->get('/public-store/'.$shop->id);

        $response->assertStatus(200);
    }

    public function test_index_excludes_sold_items(): void
    {
        $shop = $this->createShop();

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => now(),
            'hold' => null,
            'model' => 'Sold iPhone',
        ]);

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'Available iPhone',
        ]);

        $response = $this->get('/public-store/'.$shop->slug);

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.model', 'Available iPhone')
        );
    }

    public function test_index_excludes_hold_items(): void
    {
        $shop = $this->createShop();

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => now(),
            'model' => 'On Hold iPhone',
        ]);

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'Available iPhone',
        ]);

        $response = $this->get('/public-store/'.$shop->slug);

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.model', 'Available iPhone')
        );
    }

    public function test_get_unique_models_by_manufacturer(): void
    {
        $shop = $this->createShop();

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'iPhone 15',
            'manufacturer' => 'Apple',
            'type' => 'device',
        ]);

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'iPhone 14',
            'manufacturer' => 'Apple',
            'type' => 'device',
        ]);

        $response = $this->post('/public-store/get-unique-models', [
            'manufacturers' => ['Apple'],
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('models', $data);
        $this->assertNotEmpty($data['models']);
    }

    public function test_get_unique_models_requires_manufacturers(): void
    {
        $response = $this->post('/public-store/get-unique-models', []);

        $response->assertStatus(302);
    }

    public function test_get_unique_models_excludes_sold_items(): void
    {
        $shop = $this->createShop();

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => now(),
            'hold' => null,
            'model' => 'iPhone 15',
            'manufacturer' => 'Apple',
            'type' => 'device',
        ]);

        $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'sold' => null,
            'hold' => null,
            'model' => 'iPhone 14',
            'manufacturer' => 'Apple',
            'type' => 'device',
        ]);

        $response = $this->post('/public-store/get-unique-models', [
            'manufacturers' => ['Apple'],
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data['models']);
    }

    public function test_items_request_ignores_frontend_prices_to_prevent_tampering(): void
    {
        $shop = $this->createShop();

        // Create an item with a real price of 1000 in DB
        $item = $this->createItem([
            'shop_id' => $shop->id,
            'user_id' => $this->owner->id,
            'selling_price' => 1000.00,
            'model' => 'iPhone 15 Pro',
        ]);

        // Attempt to request the item from the frontend, but tampering the price to 0.01
        $response = $this->postJson('/publicInventory/request', [
            'name' => 'Hacker User',
            'email' => 'hacker@example.com',
            'store' => 'Test Store',
            'notes' => 'I changed the price!',
            'items' => [
                [
                    'id' => $item->id,
                    'selling_price' => 0.01, // Tampered price
                    'currency' => 'CAD',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'saved' => true,
        ]);

        $responseData = $response->json();
        $requestId = $responseData['id'];

        // Retrieve the saved incoming request item from DB
        $savedRequestItem = \App\Models\IncomingRequestItem::where('incoming_request_id', $requestId)
            ->where('original_item_id', $item->id)
            ->first();

        $this->assertNotNull($savedRequestItem);
        // The saved price MUST be the database price (1000), NOT the tampered frontend price (0.01)
        $this->assertEquals(1000.00, $savedRequestItem->selling_price);
        $this->assertEquals('CAD', $savedRequestItem->currency);
    }
}
