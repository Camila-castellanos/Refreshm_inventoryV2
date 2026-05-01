<?php

namespace Tests\Feature\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesControllerCacheTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force database cache driver for testing invalidation logic
        config(['cache.default' => 'database']);

        $this->user = User::factory()->create(['role' => 'OWNER']);
        $store = Store::factory()->create();
        $this->user->update(['store_id' => $store->id]);
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_expense_is_stored(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['expenseMetrics' => ['totalExpenses' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $expensePayload = [
            'items' => [
                [
                    'name' => 'Test Expense',
                    'category' => 'Utilities',
                    'amount' => 100,
                    'tax' => 0,
                    'total' => 100,
                    'date' => Carbon::now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->post(route('expenses.store'), $expensePayload);

        $response->assertStatus(201);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after expense creation');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_expense_is_updated(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['expenseMetrics' => ['totalExpenses' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        $updatePayload = [
            'items' => [
                [
                    'id' => $expense->id,
                    'name' => 'Updated Expense',
                    'category' => 'Maintenance',
                    'amount' => 200,
                    'tax' => 0,
                    'total' => 200,
                    'date' => Carbon::now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->post(route('expenses.update'), $updatePayload);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after expense update');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_expenses_are_obliterated(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['expenseMetrics' => ['totalExpenses' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete(route('expenses.obliterate'), [['id' => $expense->id]]);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after expense obliteration');
    }

    /** @test */
    public function it_does_not_invalidate_other_users_cache(): void
    {
        $otherUser = User::factory()->create();
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $otherUserCacheKey = "dashboard_metrics_v2_{$otherUser->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($otherUserCacheKey, ['data' => 'secret'], now()->addMinutes(30));
        $this->assertTrue(Cache::has($otherUserCacheKey));

        $this->actingAs($this->user);
        $expensePayload = [
            'items' => [
                [
                    'name' => 'Test Expense',
                    'category' => 'Utilities',
                    'amount' => 100,
                    'tax' => 0,
                    'total' => 100,
                    'date' => Carbon::now()->format('Y-m-d'),
                ]
            ]
        ];

        $this->post(route('expenses.store'), $expensePayload);

        $this->assertTrue(Cache::has($otherUserCacheKey), "Other user's cache should remain intact");
    }
}
