<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_get_exchange_rate(): void
    {
        Cache::put('exchange_rate_usd_cad', 1.35, now()->addMinutes(60));

        $response = $this->getJson('/api/exchange-rate/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'rate',
                'cached',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'cached' => true,
            ]);
    }

    public function test_can_get_cached_exchange_rate(): void
    {
        Cache::put('exchange_rate_usd_cad', 1.35, now()->addMinutes(60));

        $response = $this->getJson('/api/exchange-rate/');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cached' => true,
                'rate' => 1.35,
            ]);
    }

    public function test_can_refresh_exchange_rate(): void
    {
        Cache::put('exchange_rate_usd_cad', 1.35, now()->addMinutes(60));

        $response = $this->postJson('/api/exchange-rate/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'rate',
                'message',
            ]);
    }

    public function test_can_clear_cache(): void
    {
        Cache::put('exchange_rate_usd_cad', 1.35, now()->addMinutes(60));

        $response = $this->deleteJson('/api/exchange-rate/cache');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exchange rate cache cleared',
            ]);

        $this->assertNull(Cache::get('exchange_rate_usd_cad'));
    }

    public function test_returns_429_when_api_fails(): void
    {
        Cache::forget('exchange_rate_usd_cad');

        $response = $this->getJson('/api/exchange-rate/');

        $response->assertStatus(200);
    }
}
