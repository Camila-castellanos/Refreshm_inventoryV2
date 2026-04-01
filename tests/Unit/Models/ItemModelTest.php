<?php

namespace Tests\Unit\Models;

use App\Models\Item;
use App\Models\ProductModel;
use App\Models\Storage;
use App\Models\Vendor;
use Tests\TestCaseWithCompany;

class ItemModelTest extends TestCaseWithCompany
{
    public function test_get_main_photo_url_returns_placeholder_when_no_photos(): void
    {
        $item = $this->createItem();

        $this->assertStringContainsString('item-placeholder.svg', $item->main_photo_url);
    }

    public function test_get_main_photo_thumb_returns_placeholder_when_no_photos(): void
    {
        $item = $this->createItem();

        $this->assertStringContainsString('item-placeholder.svg', $item->main_photo_thumb);
    }

    public function test_get_photo_urls_returns_empty_array_when_no_photos(): void
    {
        $item = $this->createItem();

        $this->assertEquals([], $item->photo_urls);
    }

    public function test_has_photos_returns_false_when_no_media(): void
    {
        $item = $this->createItem();

        $this->assertFalse($item->hasPhotos());
    }

    public function test_get_photo_count_returns_zero_when_no_photos(): void
    {
        $item = $this->createItem();

        $this->assertEquals(0, $item->photo_count);
    }

    public function test_get_vendor_name_returns_vendor_name_when_vendor_exists(): void
    {
        $item = $this->createItem([
            'vendor_id' => $this->vendor->id,
        ]);

        $this->assertEquals($this->vendor->vendor, $item->vendor_name);
    }

    public function test_get_vendor_name_returns_null_when_no_vendor(): void
    {
        $item = $this->createItem([
            'vendor_id' => null,
        ]);

        $this->assertNull($item->vendor_name);
    }

    public function test_get_next_available_position_returns_first_position_for_empty_storage(): void
    {
        $position = Item::getNextAvailablePosition($this->storage->id);

        $this->assertEquals(1, $position);
    }

    public function test_get_next_available_position_skips_occupied_positions(): void
    {
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);
        $this->createItem(['position' => 4]);

        $position = Item::getNextAvailablePosition($this->storage->id);

        $this->assertEquals(3, $position);
    }

    public function test_get_next_available_position_considers_draft_items(): void
    {
        $this->createItem(['position' => 1]);

        $draft = \App\Models\Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);
        \App\Models\DraftItem::factory()->create([
            'draft_id' => $draft->id,
            'storage_id' => $this->storage->id,
            'storage_position' => 2,
        ]);

        $position = Item::getNextAvailablePosition($this->storage->id);

        $this->assertEquals(3, $position);
    }

    public function test_item_auto_assigns_position_on_create_when_storage_set(): void
    {
        $newStorage = $this->createStorage();

        $item = Item::factory()->create([
            'user_id' => $this->owner->id,
            'storage_id' => $newStorage->id,
            'position' => null,
        ]);

        $this->assertNotNull($item->position);
        $this->assertEquals(1, $item->position);
    }

    public function test_item_auto_assigns_position_on_update_when_newly_assigned_to_storage(): void
    {
        $item = Item::factory()->create([
            'user_id' => $this->owner->id,
            'storage_id' => null,
            'position' => null,
        ]);

        $item->storage_id = $this->storage->id;
        $item->save();

        $this->assertNotNull($item->position);
    }

    public function test_item_sets_sold_date_when_sale_id_is_set(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'sale_id' => $sale->id,
        ]);

        $this->assertNotNull($item->sold);
    }

    public function test_get_sold_attribute_fallback_returns_sold_value_when_set(): void
    {
        $item = $this->createItem([
            'sold' => now()->subDay(),
        ]);

        $this->assertNotNull($item->sold);
    }

    public function test_remove_sale_clears_sale_relationship(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'sale_id' => $sale->id,
            'customer' => 'Test Customer',
            'sold' => now(),
            'sold_storage_id' => $this->storage->id,
            'sold_position' => 1,
        ]);

        $item->removeSale();

        $this->assertNull($item->sale_id);
        $this->assertNull($item->customer);
        $this->assertNull($item->sold);
    }

    public function test_remove_sale_restores_location_when_position_free(): void
    {
        $item = $this->createItem([
            'storage_id' => null,
            'position' => null,
            'sold_storage_id' => $this->storage->id,
            'sold_position' => 5,
        ]);

        $item->removeSale();

        $this->assertEquals($this->storage->id, $item->storage_id);
        $this->assertEquals(5, $item->position);
        $this->assertNull($item->sold_storage_id);
    }

    public function test_remove_sale_does_not_restore_when_position_occupied(): void
    {
        $this->createItem(['position' => 5]);

        $item = $this->createItem([
            'storage_id' => null,
            'position' => null,
            'sold_storage_id' => $this->storage->id,
            'sold_position' => 5,
        ]);

        $item->removeSale();

        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }

    public function test_item_belongs_to_storage_relationship(): void
    {
        $item = $this->createItem([
            'storage_id' => $this->storage->id,
        ]);

        $this->assertInstanceOf(Storage::class, $item->storage);
    }

    public function test_item_belongs_to_vendor_relationship(): void
    {
        $item = $this->createItem([
            'vendor_id' => $this->vendor->id,
        ]);

        $this->assertInstanceOf(Vendor::class, $item->vendor);
    }

    public function test_item_belongs_to_product_model_relationship(): void
    {
        $productModel = ProductModel::factory()->create();

        $item = $this->createItem([
            'product_model_id' => $productModel->id,
        ]);

        $this->assertInstanceOf(ProductModel::class, $item->productModel);
    }

    public function test_item_has_many_items_through_sale(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->createItem(['sale_id' => $sale->id]);
        $this->createItem(['sale_id' => $sale->id]);

        $this->assertCount(2, $sale->items);
    }
}
