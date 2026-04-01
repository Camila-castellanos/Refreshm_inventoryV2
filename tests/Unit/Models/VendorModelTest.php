<?php

namespace Tests\Unit\Models;

use App\Models\Vendor;
use Tests\TestCaseWithCompany;

class VendorModelTest extends TestCaseWithCompany
{
    public function test_vendor_has_many_items(): void
    {
        $vendor = Vendor::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->createItem(['vendor_id' => $vendor->id]);
        $this->createItem(['vendor_id' => $vendor->id]);

        $this->assertCount(2, $vendor->items);
    }
}
