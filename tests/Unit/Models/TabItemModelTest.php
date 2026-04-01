<?php

namespace Tests\Unit\Models;

use App\Models\Tab;
use App\Models\TabItem;
use Tests\TestCaseWithCompany;

class TabItemModelTest extends TestCaseWithCompany
{
    public function test_tab_item_get_tab_returns_tab(): void
    {
        $tab = Tab::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $found = TabItem::getTab($tab->id);

        $this->assertEquals($tab->id, $found->id);
    }

    public function test_tab_item_get_tab_returns_null_for_nonexistent(): void
    {
        $found = TabItem::getTab(99999);

        $this->assertNull($found);
    }
}
