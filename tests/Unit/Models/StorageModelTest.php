<?php

namespace Tests\Unit\Models;

use App\Models\Draft;
use App\Models\DraftItem;
use App\Models\Storage;
use Tests\TestCaseWithCompany;

class StorageModelTest extends TestCaseWithCompany
{
    public function test_get_occupied_positions_returns_empty_array_for_empty_storage(): void
    {
        $positions = Storage::getOccupiedPositions($this->storage->id);

        $this->assertEquals([], $positions);
    }

    public function test_get_occupied_positions_returns_item_positions(): void
    {
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 3]);
        $this->createItem(['position' => 5]);

        $positions = Storage::getOccupiedPositions($this->storage->id);

        $this->assertCount(3, $positions);
        $this->assertContains(1, $positions);
        $this->assertContains(3, $positions);
        $this->assertContains(5, $positions);
    }

    public function test_get_occupied_positions_ignores_sold_items(): void
    {
        $this->createItem(['position' => 1, 'sold' => now()]);
        $this->createItem(['position' => 2, 'sold' => null]);

        $positions = Storage::getOccupiedPositions($this->storage->id);

        $this->assertCount(1, $positions);
        $this->assertContains(2, $positions);
    }

    public function test_get_occupied_positions_includes_draft_items(): void
    {
        $this->createItem(['position' => 1]);

        $draft = Draft::factory()->create(['user_id' => $this->owner->id]);
        DraftItem::factory()->create([
            'draft_id' => $draft->id,
            'storage_id' => $this->storage->id,
            'storage_position' => 2,
        ]);

        $positions = Storage::getOccupiedPositions($this->storage->id);

        $this->assertCount(2, $positions);
        $this->assertContains(1, $positions);
        $this->assertContains(2, $positions);
    }

    public function test_get_occupied_positions_excludes_specific_draft(): void
    {
        $draft1 = Draft::factory()->create(['user_id' => $this->owner->id]);
        $draft2 = Draft::factory()->create(['user_id' => $this->owner->id]);

        DraftItem::factory()->create([
            'draft_id' => $draft1->id,
            'storage_id' => $this->storage->id,
            'storage_position' => 1,
        ]);
        DraftItem::factory()->create([
            'draft_id' => $draft2->id,
            'storage_id' => $this->storage->id,
            'storage_position' => 2,
        ]);

        $positions = Storage::getOccupiedPositions($this->storage->id, $draft1->id);

        $this->assertCount(1, $positions);
        $this->assertContains(2, $positions);
    }

    public function test_get_occupied_positions_batch_multiple_storages(): void
    {
        $storage2 = $this->createStorage();

        $this->createItem(['position' => 1]);
        $this->createItem(['storage_id' => $storage2->id, 'position' => 1]);

        $result = Storage::getOccupiedPositionsBatch([$this->storage->id, $storage2->id]);

        $this->assertCount(2, $result);
        $this->assertCount(1, $result[$this->storage->id]);
        $this->assertCount(1, $result[$storage2->id]);
    }

    public function test_get_occupied_count_returns_correct_count(): void
    {
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);

        $count = Storage::getOccupiedCount($this->storage->id);

        $this->assertEquals(2, $count);
    }

    public function test_get_available_slots_returns_limit_minus_occupied(): void
    {
        $this->storage->update(['limit' => 10]);
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);
        $this->createItem(['position' => 3]);

        $slots = Storage::getAvailableSlots($this->storage->id);

        $this->assertEquals(7, $slots);
    }

    public function test_get_available_slots_returns_zero_when_full(): void
    {
        $this->storage->update(['limit' => 2]);
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);

        $slots = Storage::getAvailableSlots($this->storage->id);

        $this->assertEquals(0, $slots);
    }

    public function test_get_available_slots_never_returns_negative(): void
    {
        $this->storage->update(['limit' => 2]);
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);
        $this->createItem(['position' => 3]);

        $slots = Storage::getAvailableSlots($this->storage->id);

        $this->assertEquals(0, $slots);
    }

    public function test_is_position_occupied_returns_true_when_occupied(): void
    {
        $this->createItem(['position' => 5]);

        $occupied = Storage::isPositionOccupied($this->storage->id, 5);

        $this->assertTrue($occupied);
    }

    public function test_is_position_occupied_returns_false_when_free(): void
    {
        $this->createItem(['position' => 5]);

        $occupied = Storage::isPositionOccupied($this->storage->id, 10);

        $this->assertFalse($occupied);
    }

    public function test_find_first_available_in_storage_returns_first_position(): void
    {
        $this->storage->update(['limit' => 10]);

        $position = Storage::findFirstAvailableInStorage($this->storage->id);

        $this->assertEquals(1, $position);
    }

    public function test_find_first_available_in_storage_skips_occupied(): void
    {
        $this->storage->update(['limit' => 10]);
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);

        $position = Storage::findFirstAvailableInStorage($this->storage->id);

        $this->assertEquals(3, $position);
    }

    public function test_find_first_available_in_storage_returns_null_when_full(): void
    {
        $this->storage->update(['limit' => 2]);
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);

        $position = Storage::findFirstAvailableInStorage($this->storage->id);

        $this->assertNull($position);
    }

    public function test_find_first_available_position_returns_first_available_in_priority_order(): void
    {
        $storage2 = $this->createStorage(['priority' => 2, 'limit' => 10]);

        $this->createItem(['storage_id' => $this->storage->id, 'position' => 1]);

        $result = Storage::findFirstAvailablePosition();

        $this->assertNotNull($result);
    }

    public function test_find_first_available_position_returns_null_when_all_full(): void
    {
        $this->storage->update(['limit' => 1]);
        $this->createItem(['position' => 1]);

        $result = Storage::findFirstAvailablePosition();

        $this->assertNull($result);
    }

    public function test_get_all_with_occupancy_returns_collection_with_counts(): void
    {
        $this->storage->update(['limit' => 10]);
        $this->createItem(['position' => 1]);
        $this->createItem(['position' => 2]);

        $storages = Storage::getAllWithOccupancy();

        $this->assertCount(1, $storages);
        $this->assertEquals(2, $storages->first()->occupied_count);
        $this->assertEquals(8, $storages->first()->available_slots);
    }

    public function test_deleting_storage_clears_item_positions(): void
    {
        $item = $this->createItem(['position' => 5]);
        $storageId = $this->storage->id;

        $this->storage->delete();

        $item->refresh();
        $this->assertNull($item->position);
    }

    public function test_storage_belongs_to_company(): void
    {
        $storage = Storage::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(\App\Models\Company::class, $storage->company);
    }

    public function test_storage_has_many_items(): void
    {
        $this->createItem(['storage_id' => $this->storage->id]);
        $this->createItem(['storage_id' => $this->storage->id]);

        $this->assertCount(2, $this->storage->items);
    }

    public function test_storage_has_many_draft_items(): void
    {
        $draft = Draft::factory()->create(['user_id' => $this->owner->id]);
        DraftItem::factory()->create(['draft_id' => $draft->id, 'storage_id' => $this->storage->id]);
        DraftItem::factory()->create(['draft_id' => $draft->id, 'storage_id' => $this->storage->id]);

        $this->assertCount(2, $this->storage->draftItems);
    }
}
