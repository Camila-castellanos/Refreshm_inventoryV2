<?php

namespace Tests\Unit\Models;

use App\Models\Tab;
use Tests\TestCaseWithCompany;

class TabModelTest extends TestCaseWithCompany
{
    public function test_tab_belongs_to_user(): void
    {
        $tab = Tab::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertEquals($this->owner->id, $tab->user_id);
    }
}
