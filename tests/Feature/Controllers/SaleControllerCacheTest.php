<?php

namespace Tests\Feature\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Store;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleControllerCacheTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['cache.default' => 'database']);

        $company = Company::factory()->create();
        // Create user with associated store (required for auth context)
        $this->user = User::factory()->create([
            'role' => 'OWNER',
            'company_id' => $company->id
        ]);
        $store = Store::factory()->create();
        $this->user->update(['store_id' => $store->id]);
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_sale_is_stored(): void
    {
        $this->actingAs($this->user);

        // Generate the same cache key used by DashboardController
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        // Pre-populate cache with dummy dashboard data
        Cache::put($cacheKey, ['salesMetrics' => ['soldValueThisMonth' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey), 'Cache should be pre-populated before sale creation');

        // Create required related models for sale creation
        $customer = Customer::factory()->create();
        $item = Item::factory()->create([
            'user_id' => $this->user->id,
            'sold' => null,
            'sale_id' => null,
            'storage_id' => null,
            'position' => null,
        ]);

        // Prepare valid sale store payload matching SaleForm validation rules
        $salePayload = [
            'items' => [
                [
                    'id' => $item->id,
                    'type' => 'device',
                    'customer' => $customer->id,
                    'selling_price' => 500,
                    'cost' => 300,
                    'sold' => Carbon::now()->format('Y-m-d H:i:s'),
                    'profit' => 200,
                ]
            ],
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 10,
            'flatTax' => 50,
            'total' => 550,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'amount_paid' => 550,
            'paid' => 1,
            'credit' => 0,
        ];

        // Trigger sale store
        $response = $this->post(route('sales.store'), $salePayload);

        // Assert sale was created successfully
        $response->assertStatus(201);

        // Assert dashboard cache was invalidated
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after sale creation');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_sale_is_updated(): void
    {
        $this->actingAs($this->user);

        // Generate cache key
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        // Pre-populate cache
        Cache::put($cacheKey, ['salesMetrics' => ['soldValueThisMonth' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        // Create a sale to update
        $customer = Customer::factory()->create();
        $item = Item::factory()->create([
            'user_id' => $this->user->id,
            'sold' => Carbon::now(),
            'sale_id' => null,
        ]);
        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->user->id,
            'subtotal' => 500,
            'total' => 550,
            'balance_remaining' => 0,
        ]);
        $item->update(['sale_id' => $sale->id]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);

        // Prepare update payload matching SaleFormEdit validation rules
        $updatePayload = [
            'id' => $sale->id,
            'items' => [
                [
                    'id' => $item->id,
                    'selling_price' => 600,
                    'cost' => 300,
                    'customer' => $customer->id,
                    'sold' => Carbon::now()->format('Y-m-d H:i:s'),
                    'profit' => 300,
                    'type' => 'device',
                ]
            ],
            'subtotal' => 600,
            'discount' => 0,
            'tax' => 10,
            'total' => 660,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'date' => Carbon::now()->format('Y-m-d'),
            'customer' => $customer->customer, // Must be string per validation
            'credit' => 0,
            'paid' => 1,
            'amount_paid' => 660,
            'flatTax' => 60,
        ];

        // Trigger sale update
        $response = $this->post(route('sales.update'), $updatePayload);

        // Debug: dump response content and sale ID
        dump('Sale ID: ' . $sale->id);
        dump('Response status: ' . $response->getStatusCode());
        dump('Response content: ' . $response->getContent());

        // Assert update was successful
        $response->assertStatus(201);

        // Assert cache was invalidated
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after sale update');
    }
}
