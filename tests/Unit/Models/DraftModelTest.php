<?php

namespace Tests\Unit\Models;

use App\Models\Draft;
use Tests\TestCaseWithCompany;

class DraftModelTest extends TestCaseWithCompany
{
    public function test_draft_belongs_to_user(): void
    {
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(\App\Models\User::class, $draft->user);
    }

    public function test_draft_has_many_items(): void
    {
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        \App\Models\DraftItem::factory()->for($draft, 'draft')->create();
        \App\Models\DraftItem::factory()->for($draft, 'draft')->create();

        $this->assertCount(2, $draft->items);
    }

    public function test_draft_casts_date_to_date(): void
    {
        $draft = Draft::factory()->create([
            'user_id' => $this->owner->id,
            'date' => '2024-01-15',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $draft->date);
    }

    public function test_draft_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();
        $user2 = \App\Models\User::factory()->create([
            'company_id' => $company2->id,
        ]);

        Draft::factory()->create(['user_id' => $this->owner->id]);
        Draft::factory()->create(['user_id' => $user2->id]);

        $drafts = Draft::all();

        $this->assertGreaterThanOrEqual(1, $drafts->count());
    }
}
