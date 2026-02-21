<?php

namespace Tests\Feature\InventoryPublic;

use Tests\TestCaseWithCompany;

class InventoryPublicTest extends TestCaseWithCompany
{
    public function test_index_returns_404_for_invalid_shop(): void
    {
        $response = $this->get('/publicstore/invalid-shop-12345');

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

        $response = $this->get('/publicstore/'.$shop->slug);

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

        $response = $this->get('/publicstore/'.$shop->id);

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

        $response = $this->get('/publicstore/'.$shop->slug);

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

        $response = $this->get('/publicstore/'.$shop->slug);

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

        $response = $this->post('/publicstore/get-unique-models', [
            'manufacturers' => ['Apple'],
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('models', $data);
        $this->assertNotEmpty($data['models']);
    }

    public function test_get_unique_models_requires_manufacturers(): void
    {
        $response = $this->post('/publicstore/get-unique-models', []);

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

        $response = $this->post('/publicstore/get-unique-models', [
            'manufacturers' => ['Apple'],
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data['models']);
    }
}
