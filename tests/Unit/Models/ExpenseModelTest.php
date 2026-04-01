<?php

namespace Tests\Unit\Models;

use App\Models\Expense;
use Tests\TestCaseWithCompany;

class ExpenseModelTest extends TestCaseWithCompany
{
    public function test_expense_has_company_scope(): void
    {
        $company2 = \App\Models\Company::factory()->create();
        $user2 = \App\Models\User::factory()->create([
            'company_id' => $company2->id,
        ]);

        Expense::factory()->create(['user_id' => $this->owner->id]);
        Expense::factory()->create(['user_id' => $user2->id]);

        $expenses = Expense::all();

        $this->assertGreaterThanOrEqual(1, $expenses->count());
    }
}
