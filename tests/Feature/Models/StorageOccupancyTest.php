<?php

namespace Tests\Feature\Models;

use App\Models\Item;
use App\Models\Storage;
use Tests\TestCaseWithCompany;

class StorageOccupancyTest extends TestCaseWithCompany
{
    public function test_get_occupied_positions_includes_reserved_items(): void
    {
        $storage = $this->createStorage(['limit' => 10]);
        
        // Available item
        $this->createItem([
            'storage_id' => $storage->id,
            'position' => 1,
            'status' => Item::STATUS_AVAILABLE,
        ]);

        // Reserved item
        $this->createItem([
            'storage_id' => $storage->id,
            'position' => 2,
            'status' => Item::STATUS_RESERVED,
        ]);

        // Sold item (should not be in this storage/position usually, but testing the query)
        $this->createItem([
            'storage_id' => $storage->id,
            'position' => 3,
            'status' => Item::STATUS_SOLD,
        ]);

        $occupied = Storage::getOccupiedPositions($storage->id);

        $this->assertContains(1, $occupied);
        $this->assertContains(2, $occupied);
        $this->assertNotContains(3, $occupied, 'Sold items should not be considered occupied even if they have a position (though they shouldn\'t)');
    }

    public function test_get_occupied_positions_batch_includes_reserved_items(): void
    {
        $storage = $this->createStorage(['limit' => 10]);
        
        $this->createItem([
            'storage_id' => $storage->id,
            'position' => 5,
            'status' => Item::STATUS_RESERVED,
        ]);

        $batch = Storage::getOccupiedPositionsBatch([$storage->id]);

        $this->assertContains(5, $batch[$storage->id]);
    }
}
