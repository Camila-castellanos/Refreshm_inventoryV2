<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCaseWithCompany;

class ItemSavingHookTest extends TestCaseWithCompany
{
    /** @test */
    public function test_saving_hook_does_not_clobber_existing_sold_storage_id_on_resave(): void
    {
        // Item already SOLD with a snapshot from a prior save.
        $item = $this->createItem([
            'status' => 'sold',
            'sold' => Carbon::now()->subDay(),
            'storage_id' => null,
            'position' => null,
            'sold_storage_id' => $this->storage->id,
            'sold_position' => 3,
            'sold_storage_name' => $this->storage->name,
        ]);

        // Save again with no real changes — the boot hook MUST see the
        // ! is_null($item->sold_storage_id) guard (Item.php:349) and skip
        // the snapshot block, leaving the existing snapshot intact.
        $item->save();
        $item->refresh();

        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertEquals(3, $item->sold_position);
        $this->assertEquals($this->storage->name, $item->sold_storage_name);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }

    /** @test */
    public function test_saving_hook_snapshots_real_location_on_first_sold_transition(): void
    {
        $item = $this->createItem([
            'status' => 'reserved',
            'storage_id' => $this->storage->id,
            'position' => 4,
        ]);

        // Transition to SOLD. Saving hook sees storage_id non-null and
        // sold_storage_id null, so it snapshots and then nulls active fields.
        $item->status = 'sold';
        $item->sold = Carbon::now();
        $item->save();
        $item->refresh();

        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertEquals(4, $item->sold_position);
        $this->assertEquals($this->storage->name, $item->sold_storage_name);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }
}
