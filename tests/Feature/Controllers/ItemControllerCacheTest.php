<?php

namespace Tests\Feature\Controllers;

use App\Models\Item;
use App\Models\User;
use App\Models\Store;
use App\Models\Company;
use App\Models\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemControllerCacheTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Storage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['cache.default' => 'database']);

        $company = Company::factory()->create();
        $this->user = User::factory()->create([
            'role' => 'OWNER',
            'company_id' => $company->id
        ]);
        $store = Store::factory()->create();
        $this->user->update(['store_id' => $store->id]);
        
        $this->storage = Storage::factory()->create(['company_id' => $company->id]);
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_items_are_stored(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['inventoryMetrics' => ['totalValue' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $itemPayload = [
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'cost' => 500.00,
                    'selling_price' => 699.99,
                    'storage_id' => $this->storage->id,
                    'position' => 1,
                    'imei' => '123456789012345',
                ]
            ]
        ];

        $response = $this->post(route('items.store'), $itemPayload);

        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after item creation');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_items_are_updated(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['inventoryMetrics' => ['totalValue' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $item = Item::factory()->create(['user_id' => $this->user->id, 'storage_id' => $this->storage->id]);

        $updatePayload = [
            'items' => [
                [
                    'id' => $item->id,
                    'model' => 'iPhone 15 Updated',
                    'selling_price' => 799.99,
                    'storage_id' => $this->storage->id,
                ]
            ]
        ];

        $response = $this->post(route('items.update'), $updatePayload);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after item update');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_items_are_obliterated(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['inventoryMetrics' => ['totalValue' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $item = Item::factory()->create(['user_id' => $this->user->id, 'storage_id' => $this->storage->id]);

        $response = $this->delete(route('items.obliterate'), [['id' => $item->id]]);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after item obliteration');
    }

    /** @test */
    public function it_invalidates_dashboard_cache_when_item_is_destroyed(): void
    {
        $this->actingAs($this->user);

        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();
        $cacheKey = "dashboard_metrics_v2_{$this->user->id}_{$startOfMonth}_{$endOfMonth}";

        Cache::put($cacheKey, ['inventoryMetrics' => ['totalValue' => 1000]], now()->addMinutes(30));
        $this->assertTrue(Cache::has($cacheKey));

        $item = Item::factory()->create(['user_id' => $this->user->id, 'storage_id' => $this->storage->id]);

        $response = $this->delete(route('items.destroy', $item));

        // If destroy doesn't exist yet, this will fail with 404 or 500
        $response->assertStatus(200);
        $this->assertFalse(Cache::has($cacheKey), 'Dashboard cache should be invalidated after item deletion');
    }
}
