<?php

namespace Tests\Unit\Models;

use App\Models\Draft;
use App\Models\DraftItem;
use Tests\TestCaseWithCompany;

class DraftItemModelTest extends TestCaseWithCompany
{
    public function test_draft_item_belongs_to_draft(): void
    {
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = DraftItem::factory()->for($draft, 'draft')->create();

        $this->assertInstanceOf(Draft::class, $item->draft);
    }

    public function test_draft_item_belongs_to_vendor(): void
    {
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = DraftItem::factory()->for($draft, 'draft')->for($this->vendor, 'vendor')->create();

        $this->assertInstanceOf(\App\Models\Vendor::class, $item->vendor);
    }

    public function test_draft_item_belongs_to_tax(): void
    {
        $tax = \App\Models\Tax::factory()->create();
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = DraftItem::factory()->for($draft, 'draft')->for($tax, 'tax')->create();

        $this->assertInstanceOf(\App\Models\Tax::class, $item->tax);
    }

    public function test_draft_item_belongs_to_storage(): void
    {
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $item = DraftItem::factory()->for($draft, 'draft')->for($this->storage, 'storage')->create();

        $this->assertInstanceOf(\App\Models\Storage::class, $item->storage);
    }
}
