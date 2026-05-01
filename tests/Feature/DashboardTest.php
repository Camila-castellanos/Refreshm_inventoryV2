<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_expense_breakdown()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => 'OWNER', 'company_id' => $company->id]);
        $this->actingAs($user);

        // Clear cache to ensure fresh state
        \Illuminate\Support\Facades\Cache::flush();

        // Create some expenses for this user
        Expense::factory()->create([
            'category' => 'Rent',
            'total' => 1500, // Matched the expected total in line 51
            'date' => now()->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        Expense::factory()->create([
            'category' => 'Utilities',
            'total' => 200,
            'date' => now()->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('expenseBreakdown', 2)
            ->where('expenseBreakdown.0.category', 'Rent')
            ->where('expenseBreakdown.0.total', 1500)
            ->where('expenseBreakdown.1.category', 'Utilities')
            ->where('expenseBreakdown.1.total', 200)
        );
    }

    public function test_dashboard_returns_empty_expense_breakdown_when_no_expenses_exist()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => 'OWNER', 'company_id' => $company->id]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('expenseBreakdown', 0)
        );
    }

    public function test_dashboard_returns_expense_breakdown_with_null_category()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => 'OWNER', 'company_id' => $company->id]);
        $this->actingAs($user);

        Expense::factory()->create([
            'category' => null,
            'total' => 1000,
            'date' => now()->format('Y-m-d'),
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('expenseBreakdown', 1)
            ->where('expenseBreakdown.0.category', null)
            ->where('expenseBreakdown.0.total', 1000)
        );
    }
}
