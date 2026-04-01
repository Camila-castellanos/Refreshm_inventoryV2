<?php

namespace Tests\Unit\Models;

use App\Models\ProductModel;
use Tests\TestCaseWithCompany;

class ProductModelTest extends TestCaseWithCompany
{
    public function test_get_main_photo_url_returns_placeholder_when_no_photos(): void
    {
        $productModel = ProductModel::factory()->create();

        $this->assertStringContainsString('item-placeholder.svg', $productModel->main_photo_url);
    }

    public function test_get_main_photo_thumb_returns_placeholder_when_no_photos(): void
    {
        $productModel = ProductModel::factory()->create();

        $this->assertStringContainsString('item-placeholder.svg', $productModel->main_photo_thumb);
    }

    public function test_get_photo_urls_returns_empty_array_when_no_photos(): void
    {
        $productModel = ProductModel::factory()->create();

        $this->assertEquals([], $productModel->photo_urls);
    }

    public function test_has_photos_returns_false_when_no_media(): void
    {
        $productModel = ProductModel::factory()->create();

        $this->assertFalse($productModel->hasPhotos());
    }

    public function test_get_photo_count_returns_zero_when_no_photos(): void
    {
        $productModel = ProductModel::factory()->create();

        $this->assertEquals(0, $productModel->photo_count);
    }

    public function test_get_photo_by_colour_returns_null_when_not_found(): void
    {
        $productModel = ProductModel::factory()->create();

        $photo = $productModel->getPhotoByColour('Red');

        $this->assertNull($photo);
    }

    public function test_get_first_media_url_by_colour_returns_placeholder_when_not_found(): void
    {
        $productModel = ProductModel::factory()->create();

        $url = $productModel->getFirstMediaUrlByColour('Red', 'preview');

        $this->assertEquals(asset('images/item-placeholder.svg'), $url);
    }

    public function test_get_all_colours_from_photos_returns_empty_array_when_no_photos(): void
    {
        $productModel = ProductModel::factory()->create();

        $colours = $productModel->getAllColoursFromPhotos();

        $this->assertEquals([], $colours);
    }

    public function test_get_all_capacities_from_photos_returns_empty_array_when_no_photos(): void
    {
        $productModel = ProductModel::factory()->create();

        $capacities = $productModel->getAllCapacitiesFromPhotos();

        $this->assertEquals([], $capacities);
    }

    public function test_product_model_has_many_items(): void
    {
        $productModel = ProductModel::factory()->create();

        $this->createItem(['product_model_id' => $productModel->id]);
        $this->createItem(['product_model_id' => $productModel->id]);

        $this->assertCount(2, $productModel->items);
    }

    public function test_product_model_casts_colours_to_array(): void
    {
        $productModel = ProductModel::factory()->create([
            'colours' => ['Black', 'White', 'Blue'],
        ]);

        $this->assertIsArray($productModel->colours);
        $this->assertCount(3, $productModel->colours);
    }

    public function test_product_model_casts_capacities_to_array(): void
    {
        $productModel = ProductModel::factory()->create([
            'capacities' => ['64GB', '128GB', '256GB'],
        ]);

        $this->assertIsArray($productModel->capacities);
        $this->assertCount(3, $productModel->capacities);
    }
}
