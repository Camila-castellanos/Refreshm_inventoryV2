<?php

namespace Tests\Unit\Models;

use App\Models\CashOnHand;
use Tests\TestCaseWithCompany;

class CashOnHandModelTest extends TestCaseWithCompany
{
    public function test_cash_on_hand_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();
        $user2 = \App\Models\User::factory()->create([
            'company_id' => $company2->id,
        ]);

        CashOnHand::factory()->create(['user_id' => $this->owner->id]);
        CashOnHand::factory()->create(['user_id' => $user2->id]);

        $cashOnHand = CashOnHand::all();

        $this->assertGreaterThanOrEqual(1, $cashOnHand->count());
    }
}
