<?php

namespace Tests\Unit\Models;

use App\Models\Item;
use App\Models\ProductModel;
use App\Models\Storage;
use App\Models\Vendor;
use Carbon\Carbon;
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

    public function test_item_sets_reserved_status_when_sale_id_is_set(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'sale_id' => $sale->id,
        ]);

        $this->assertEquals('reserved', $item->status);
        $this->assertNull($item->sold);
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

    public function test_item_has_default_status_available(): void
    {
        $item = $this->createItem();

        $this->assertEquals('available', $item->status);
    }

    public function test_item_status_can_be_reserved_or_sold(): void
    {
        $sale = \App\Models\Sale::factory()->create(['user_id' => $this->owner->id]);
        $item = $this->createItem([
            'sale_id' => $sale->id,
            'status' => 'reserved',
        ]);
        $this->assertEquals('reserved', $item->status);

        $item->sold = now();
        $item->save();
        $this->assertEquals('sold', $item->status);
    }

    public function test_scope_available_returns_only_available_items(): void
    {
        $sale = \App\Models\Sale::factory()->create(['user_id' => $this->owner->id]);

        $this->createItem(['status' => 'available']);
        $this->createItem(['status' => 'reserved', 'sale_id' => $sale->id]);
        $this->createItem(['status' => 'sold', 'sold' => now()]);

        $this->assertCount(1, Item::available()->get());
    }

    public function test_remove_sale_on_reserved_item_resets_status_and_keeps_position(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'status' => 'reserved',
            'storage_id' => $this->storage->id,
            'position' => 10,
            'sale_id' => $sale->id,
        ]);

        $item->removeSale();

        $this->assertEquals('available', $item->status);
        $this->assertNull($item->sale_id);
        $this->assertEquals($this->storage->id, $item->storage_id);
        $this->assertEquals(10, $item->position);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Partially Sold At Tests (sold-date-tracking SDD)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_partially_sold_at_is_set_when_item_transitions_to_reserved(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'status' => 'available',
            'partially_sold_at' => null,
        ]);

        // Simulate transitioning to reserved by setting sale_id
        $item->sale_id = $sale->id;
        $item->save();

        $this->assertNotNull($item->partially_sold_at);
        $this->assertEquals('reserved', $item->status);
    }

    public function test_partially_sold_at_is_not_overwritten_if_already_set(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $originalDate = now()->subDays(5);
        $item = $this->createItem([
            'status' => 'reserved',
            'sale_id' => $sale->id,
            'partially_sold_at' => $originalDate,
        ]);

        // Update some other field to trigger a save
        $item->issues = 'Screen crack';
        $item->save();

        $this->assertEquals($originalDate->toDateTimeString(), $item->partially_sold_at->toDateTimeString());
    }

    public function test_partially_sold_at_is_preserved_when_remove_sale_is_called(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'status' => 'reserved',
            'sale_id' => $sale->id,
            'customer' => 'Test Customer',
            'partially_sold_at' => now()->subDays(3),
        ]);

        $item->removeSale();

        $this->assertNull($item->sale_id);
        $this->assertNull($item->customer);
        $this->assertNotNull($item->partially_sold_at);
    }

    public function test_partially_sold_at_is_preserved_when_item_goes_back_to_available(): void
    {
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = $this->createItem([
            'status' => 'reserved',
            'sale_id' => $sale->id,
            'partially_sold_at' => now()->subDays(2),
        ]);

        $item->markAsAvailable();

        $this->assertEquals('available', $item->status);
        $this->assertNull($item->sale_id);
        $this->assertNotNull($item->partially_sold_at);
    }

    public function test_partially_sold_at_is_null_for_available_items_without_sale(): void
    {
        $item = $this->createItem([
            'status' => 'available',
            'sale_id' => null,
        ]);

        $this->assertNull($item->partially_sold_at);
    }

    public function test_partially_sold_at_fallback_used_when_sold_is_null(): void
    {
        // This tests the scenario where an item is reserved (sold=null, sale_id set)
        // and partially_sold_at should be used as the fallback for display
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
            'created_at' => now()->subDays(10),
        ]);

        $item = $this->createItem([
            'status' => 'reserved',
            'sale_id' => $sale->id,
            'sold' => null,
            'partially_sold_at' => now()->subDays(5),
        ]);

        // The item should use partially_sold_at as the fallback for "sold" date
        $soldDate = $item->sold
            ? Carbon::parse($item->sold)->format('Y-m-d')
            : ($item->partially_sold_at ? Carbon::parse($item->partially_sold_at)->format('Y-m-d') : null);

        $this->assertEquals(now()->subDays(5)->format('Y-m-d'), $soldDate);
    }
}
