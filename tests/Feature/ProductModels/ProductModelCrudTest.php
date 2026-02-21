<?php

namespace Tests\Feature\ProductModels;

use App\Models\ProductModel;
use Tests\TestCaseWithCompany;

class ProductModelCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_product_models_page(): void
    {
        ProductModel::factory()->create();
        ProductModel::factory()->create(['name' => 'iPhone 15 Pro']);

        $response = $this->actingAs($this->owner)
            ->get('/product-models');

        $response->assertStatus(200);
    }

    public function test_index_filters_by_search(): void
    {
        ProductModel::factory()->create(['name' => 'iPhone 15', 'manufacturer' => 'Apple']);
        ProductModel::factory()->create(['name' => 'Galaxy S24', 'manufacturer' => 'Samsung']);

        $response = $this->actingAs($this->owner)
            ->get('/product-models?search=iPhone');

        $response->assertStatus(200);
    }

    public function test_index_filters_by_type(): void
    {
        ProductModel::factory()->create(['name' => 'iPhone 15', 'type' => 'device']);
        ProductModel::factory()->create(['name' => 'Generic Case', 'type' => 'accessory']);

        $response = $this->actingAs($this->owner)
            ->get('/product-models?type=device');

        $response->assertStatus(200);
    }

    public function test_create_returns_create_page(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/product-models/create');

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_create_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/product-models/create');

        $response->assertStatus(302);
    }

    public function test_store_creates_product_model(): void
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'manufacturer' => 'Apple',
            'type' => 'device',
            'colours' => ['Black', 'White', 'Blue'],
            'capacities' => ['128GB', '256GB', '512GB'],
            'description' => 'Latest iPhone model',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/product-models', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_models', [
            'name' => 'iPhone 15 Pro',
            'manufacturer' => 'Apple',
            'type' => 'device',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/product-models', []);

        $response->assertSessionHasErrors('name');
    }

    public function test_edit_returns_edit_page(): void
    {
        $productModel = ProductModel::factory()->create();

        $response = $this->actingAs($this->owner)
            ->get("/product-models/{$productModel->id}/edit");

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_edit_page(): void
    {
        $productModel = ProductModel::factory()->create();

        $response = $this->actingAs($this->user)
            ->get("/product-models/{$productModel->id}/edit");

        $response->assertStatus(302);
    }

    public function test_update_modifies_product_model(): void
    {
        $productModel = ProductModel::factory()->create([
            'name' => 'iPhone 15',
            'manufacturer' => 'Apple',
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/product-models/{$productModel->id}", [
                'name' => 'iPhone 15 Pro',
                'manufacturer' => 'Apple',
                'type' => 'device',
                'colours' => ['Black'],
                'capacities' => ['256GB'],
            ]);

        $response->assertRedirect();
        $productModel->refresh();
        $this->assertEquals('iPhone 15 Pro', $productModel->name);
    }

    public function test_destroy_deletes_product_model(): void
    {
        $productModel = ProductModel::factory()->create();

        $response = $this->actingAs($this->owner)
            ->delete("/product-models/{$productModel->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('product_models', ['id' => $productModel->id]);
    }

    public function test_destroy_fails_if_has_linked_items(): void
    {
        $productModel = ProductModel::factory()->create();
        $this->createItem(['product_model_id' => $productModel->id]);

        $response = $this->actingAs($this->owner)
            ->delete("/product-models/{$productModel->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('product_models', ['id' => $productModel->id]);
    }

    public function test_regular_user_cannot_delete(): void
    {
        $productModel = ProductModel::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/product-models/{$productModel->id}");

        $response->assertStatus(302);
    }

    public function test_show_redirects_to_edit(): void
    {
        $productModel = ProductModel::factory()->create();

        $response = $this->actingAs($this->owner)
            ->get("/product-models/{$productModel->id}");

        $response->assertRedirect("/product-models/{$productModel->id}/edit");
    }

    public function test_search_returns_json(): void
    {
        ProductModel::factory()->create(['name' => 'iPhone 15', 'manufacturer' => 'Apple']);
        ProductModel::factory()->create(['name' => 'Galaxy S24', 'manufacturer' => 'Samsung']);

        $response = $this->actingAs($this->owner)
            ->get('/api/product-models/search?q=iPhone');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();
        $this->assertArrayHasKey('models', $data);
    }

    public function test_search_filters_by_type(): void
    {
        ProductModel::factory()->create(['name' => 'iPhone 15', 'type' => 'device']);
        ProductModel::factory()->create(['name' => 'iPhone Case', 'type' => 'accessory']);

        $response = $this->actingAs($this->owner)
            ->get('/api/product-models/search?q=iPhone&type=device');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['models']);
    }

    public function test_search_returns_all_matching_models(): void
    {
        ProductModel::factory()->create(['name' => 'iPhone 15']);
        ProductModel::factory()->create(['name' => 'iPhone 14']);

        $response = $this->actingAs($this->owner)
            ->get('/api/product-models/search?q=iPhone');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(2, $data['models']);
    }
}
