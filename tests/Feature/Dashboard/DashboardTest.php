<?php

namespace Tests\Feature\Dashboard;

use App\Models\Expense;
use Tests\TestCaseWithCompany;

class DashboardTest extends TestCaseWithCompany
{
    public function test_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_can_update_cash_on_hand(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 5000.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $this->owner->id,
            'balance' => 5000.00,
        ]);
    }

    public function test_can_view_expenses(): void
    {
        Expense::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/accounting/expenses');

        $response->assertStatus(200);
    }

    public function test_can_view_taxes(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/accounting/taxes');

        $response->assertStatus(200);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
