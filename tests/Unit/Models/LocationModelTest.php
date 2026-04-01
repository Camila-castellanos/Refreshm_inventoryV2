<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use Tests\TestCaseWithCompany;

class LocationModelTest extends TestCaseWithCompany
{
    public function test_location_belongs_to_store(): void
    {
        $store = \App\Models\Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $this->assertInstanceOf(\App\Models\Store::class, $location->store);
    }

    public function test_location_uses_soft_deletes(): void
    {
        $store = \App\Models\Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $location->delete();

        $this->assertNotNull($location->deleted_at);
        $this->assertTrue($location->trashed());
    }

    public function test_location_can_be_restored(): void
    {
        $store = \App\Models\Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $location = Location::factory()->create([
            'store_id' => $store->id,
        ]);

        $location->delete();
        $location->restore();

        $this->assertNull($location->deleted_at);
    }
}
