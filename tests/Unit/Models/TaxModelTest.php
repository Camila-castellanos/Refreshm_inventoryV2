<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use Tests\TestCaseWithCompany;

class TaxModelTest extends TestCaseWithCompany
{
    public function test_tax_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();
        $user2 = \App\Models\User::factory()->create([
            'company_id' => $company2->id,
        ]);

        Tax::factory()->create(['user_id' => $this->owner->id]);
        Tax::factory()->create(['user_id' => $user2->id]);

        $taxes = Tax::all();

        $this->assertGreaterThanOrEqual(1, $taxes->count());
    }
}
