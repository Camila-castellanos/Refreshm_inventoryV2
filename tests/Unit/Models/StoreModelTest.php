<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use App\Models\Store;
use Tests\TestCaseWithCompany;

class StoreModelTest extends TestCaseWithCompany
{
    public function test_store_has_many_users(): void
    {
        $store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        \App\Models\User::factory()->create([
            'store_id' => $store->id,
            'company_id' => $this->company->id,
        ]);
        \App\Models\User::factory()->create([
            'store_id' => $store->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertCount(2, $store->users);
    }

    public function test_store_has_many_locations(): void
    {
        $store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        Location::factory()->create(['store_id' => $store->id]);
        Location::factory()->create(['store_id' => $store->id]);

        $this->assertCount(2, $store->locations);
    }

    public function test_store_uses_soft_deletes(): void
    {
        $store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $store->delete();

        $this->assertNotNull($store->deleted_at);
        $this->assertTrue($store->trashed());
    }

    public function test_store_can_be_restored(): void
    {
        $store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $store->delete();
        $store->restore();

        $this->assertNull($store->deleted_at);
    }
}
