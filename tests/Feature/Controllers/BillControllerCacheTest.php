<?php

namespace Tests\Feature\Controllers;

use App\Models\Bill;
use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillControllerCacheTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['cache.default' => 'database']);

        $company = \App\Models\Company::factory()->create();
        $this->user = User::factory()->create([
            'role' => 'OWNER',
            'company_id' => $company->id
        ]);
        $store = Store::factory()->create();
        $this->user->update(['store_id' => $store->id]);
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_bill_is_stored(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['billMetrics' => ['totalUnpaid' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $billPayload = [
            'items' => [
                [
                    'name' => 'Test Bill',
                    'total' => 500,
                    'subtotal' => 500,
                    'tax' => 0,
                    'date' => Carbon::now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->post(route('bills.store'), $billPayload);

        $response->assertStatus(201);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after bill creation');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_bill_is_updated(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['billMetrics' => ['totalUnpaid' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $bill = Bill::factory()->create(['user_id' => $this->user->id]);

        $updatePayload = [
            'items' => [
                [
                    'id' => $bill->id,
                    'name' => 'Updated Bill',
                    'total' => 600,
                    'subtotal' => 600,
                    'tax' => 0,
                    'date' => Carbon::now()->format('Y-m-d'),
                    'status' => 0,
                ]
            ]
        ];

        $response = $this->post(route('bills.update'), $updatePayload);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after bill update');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_bill_is_destroyed(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['billMetrics' => ['totalUnpaid' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $bill = Bill::factory()->create(['user_id' => $this->user->id]);

        $url = route('bills.destroy', $bill);
        // dump($url);
        $response = $this->delete($url);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after bill deletion');
    }

    /** @test */
    public function it_does_not_invalidate_other_users_cache(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id
        ]);
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $otherUserCacheKey = "dashboard_metrics_v2_{$otherUser->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($otherUserCacheKey, ['data' => 'secret'], now()->addMinutes(30));
        $this->assertTrue(Cache::has($otherUserCacheKey));

        $this->actingAs($this->user);
        $billPayload = [
            'items' => [
                [
                    'name' => 'Test Bill',
                    'total' => 500,
                    'subtotal' => 500,
                    'tax' => 0,
                    'date' => Carbon::now()->format('Y-m-d'),
                ]
            ]
        ];

        $this->post(route('bills.store'), $billPayload);

        $this->assertTrue(Cache::has($otherUserCacheKey), "Other user's cache should remain intact");
    }
}
