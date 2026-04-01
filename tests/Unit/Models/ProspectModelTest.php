<?php

namespace Tests\Unit\Models;

use App\Models\Prospect;
use Tests\TestCaseWithCompany;

class ProspectModelTest extends TestCaseWithCompany
{
    public function test_prospect_belongs_to_company(): void
    {
        $prospect = Prospect::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(\App\Models\Company::class, $prospect->company);
    }

    public function test_prospect_belongs_to_user(): void
    {
        $prospect = Prospect::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(\App\Models\User::class, $prospect->user);
    }

    public function test_prospect_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();
        $user2 = \App\Models\User::factory()->create([
            'company_id' => $company2->id,
        ]);

        Prospect::factory()->create(['user_id' => $this->owner->id]);
        Prospect::factory()->create(['user_id' => $user2->id]);

        $prospects = Prospect::all();

        $this->assertGreaterThanOrEqual(1, $prospects->count());
    }
}
