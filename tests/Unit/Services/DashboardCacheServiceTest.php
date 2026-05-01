<?php

namespace Tests\Unit\Services;

use App\Services\DashboardCacheService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $service = new DashboardCacheService();
        $this->assertInstanceOf(DashboardCacheService::class, $service);
    }

    /** @test */
    public function it_has_get_cache_key_method(): void
    {
        $service = new DashboardCacheService();
        $this->assertTrue(method_exists($service, 'getCacheKey'));
    }

    /** @test */
    public function it_has_remember_method(): void
    {
        $service = new DashboardCacheService();
        $this->assertTrue(method_exists($service, 'remember'));
    }

    /** @test */
    public function it_has_invalidate_for_user_method(): void
    {
        $service = new DashboardCacheService();
        $this->assertTrue(method_exists($service, 'invalidateForUser'));
    }

    /** @test */
    public function it_has_invalidate_for_user_and_date_method(): void
    {
        $service = new DashboardCacheService();
        $this->assertTrue(method_exists($service, 'invalidateForUserAndDate'));
    }

    /** @test */
    public function it_has_clear_all_for_user_method(): void
    {
        $service = new DashboardCacheService();
        $this->assertTrue(method_exists($service, 'clearAllForUser'));
    }

    /** @test */
    public function it_is_bound_as_singleton_in_container(): void
    {
        $instance1 = app(DashboardCacheService::class);
        $instance2 = app(DashboardCacheService::class);
        
        $this->assertInstanceOf(DashboardCacheService::class, $instance1);
        $this->assertSame($instance1, $instance2, 'DashboardCacheService should be bound as singleton');
    }

    // =========================================================================
    // Task 3: getCacheKey() method
    // =========================================================================

    /** @test */
    public function get_cache_key_returns_correct_format(): void
    {
        $service = new DashboardCacheService();
        $result = $service->getCacheKey(5, '2026-05-01', '2026-05-31');
        
        $expected = 'dashboard_metrics_v2_5_2026-05-01_2026-05-31';
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function get_cache_key_works_with_different_user_ids(): void
    {
        $service = new DashboardCacheService();
        
        $result1 = $service->getCacheKey(1, '2026-05-01', '2026-05-31');
        $this->assertEquals('dashboard_metrics_v2_1_2026-05-01_2026-05-31', $result1);
        
        $result2 = $service->getCacheKey(999, '2026-01-01', '2026-12-31');
        $this->assertEquals('dashboard_metrics_v2_999_2026-01-01_2026-12-31', $result2);
    }

    // =========================================================================
    // Task 4: remember() method
    // =========================================================================

    /** @test */
    public function remember_calls_cache_remember_with_correct_parameters(): void
    {
        $service = new DashboardCacheService();
        $userId = 1;
        $startDate = '2026-05-01';
        $endDate = '2026-05-31';
        $expectedKey = 'dashboard_metrics_v2_1_2026-05-01_2026-05-31';
        $expectedResult = ['sales' => 100];

        // Mock the Cache facade
        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->once()
            ->with($expectedKey, \Mockery::type(\DateTime::class), \Mockery::type(\Closure::class))
            ->andReturn($expectedResult);

        $result = $service->remember($userId, $startDate, $endDate, function () use ($expectedResult) {
            return $expectedResult;
        });

        $this->assertEquals($expectedResult, $result);
    }

    /** @test */
    public function remember_returns_callback_result(): void
    {
        $service = new DashboardCacheService();
        $callbackResult = ['metrics' => 'test_data'];

        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->once()
            ->andReturn($callbackResult);

        $result = $service->remember(1, '2026-05-01', '2026-05-31', function () use ($callbackResult) {
            return $callbackResult;
        });

        $this->assertEquals($callbackResult, $result);
    }

    // =========================================================================
    // Task 5: Invalidation methods with DRIVER-AWARE logic
    // =========================================================================

    /** @test */
    public function invalidate_for_user_deletes_cache_keys_for_database_driver(): void
    {
        // Set config value directly
        config(['cache.default' => 'database']);
        config(['cache.prefix' => 'laravel_cache_']);

        // Mock DB facade
        $mockTable = \Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockWhere = \Mockery::mock(\Illuminate\Database\Query\Builder::class);

        \Illuminate\Support\Facades\DB::shouldReceive('table')
            ->with('cache')
            ->once()
            ->andReturn($mockTable);

        $mockTable->shouldReceive('where')
            ->with('key', 'like', 'laravel_cache_dashboard_metrics_v2_1_%')
            ->once()
            ->andReturn($mockWhere);

        $mockWhere->shouldReceive('delete')
            ->once()
            ->andReturn(1);

        $service = new DashboardCacheService();
        $service->invalidateForUser(1);
    }

    /** @test */
    public function invalidate_for_user_and_date_delegates_to_clear_all_for_user(): void
    {
        // Set config value directly
        config(['cache.default' => 'database']);
        config(['cache.prefix' => 'laravel_cache_']);

        // Mock DB facade
        $mockTable = \Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockWhere = \Mockery::mock(\Illuminate\Database\Query\Builder::class);

        \Illuminate\Support\Facades\DB::shouldReceive('table')
            ->with('cache')
            ->once()
            ->andReturn($mockTable);

        $mockTable->shouldReceive('where')
            ->with('key', 'like', 'laravel_cache_dashboard_metrics_v2_1_%')
            ->once()
            ->andReturn($mockWhere);

        $mockWhere->shouldReceive('delete')
            ->once()
            ->andReturn(1);

        $service = new DashboardCacheService();
        $service->invalidateForUserAndDate(1, '2026-05-01');
    }

    /** @test */
    public function clear_all_for_user_performs_database_delete(): void
    {
        // Set config value directly
        config(['cache.default' => 'database']);
        config(['cache.prefix' => 'laravel_cache_']);

        // Mock DB facade
        $mockTable = \Mockery::mock(\Illuminate\Database\Query\Builder::class);
        $mockWhere = \Mockery::mock(\Illuminate\Database\Query\Builder::class);

        \Illuminate\Support\Facades\DB::shouldReceive('table')
            ->with('cache')
            ->once()
            ->andReturn($mockTable);

        $mockTable->shouldReceive('where')
            ->with('key', 'like', 'laravel_cache_dashboard_metrics_v2_42_%')
            ->once()
            ->andReturn($mockWhere);

        $mockWhere->shouldReceive('delete')
            ->once()
            ->andReturn(1);

        $service = new DashboardCacheService();
        $service->clearAllForUser(42);
    }

    /** @test */
    public function invalidation_methods_catch_and_log_exceptions(): void
    {
        // Set config to database driver
        config(['cache.default' => 'database']);
        config(['cache.prefix' => 'laravel_cache_']);

        // Mock DB to throw an exception
        \Illuminate\Support\Facades\DB::shouldReceive('table')
            ->with('cache')
            ->andThrow(new \Exception('Database connection failed'));

        // Mock Log facade to expect error logging
        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::type('string'));

        $service = new DashboardCacheService();
        
        // This should NOT throw an exception - it should catch and log
        $service->invalidateForUser(1);
        
        $this->assertTrue(true, 'Exception was caught and logged');
    }

    /** @test */
    public function invalidation_does_not_propagate_exceptions(): void
    {
        // Set config to database driver
        config(['cache.default' => 'database']);
        config(['cache.prefix' => 'laravel_cache_']);

        // Mock DB to throw an exception
        \Illuminate\Support\Facades\DB::shouldReceive('table')
            ->with('cache')
            ->andThrow(new \Exception('Database error'));

        // Mock Log facade
        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        $service = new DashboardCacheService();
        
        // Test all invalidation methods - none should throw
        try {
            $service->invalidateForUser(1);
            $service->invalidateForUserAndDate(1, '2026-05-01');
            $service->clearAllForUser(1);
            $this->assertTrue(true, 'No exceptions were propagated');
        } catch (\Exception $e) {
            $this->fail('Exception should not be propagated: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // Task 7: Additional edge case tests
    // =========================================================================

    /** @test */
    public function get_cache_key_handles_empty_dates(): void
    {
        $service = new DashboardCacheService();
        $result = $service->getCacheKey(1, '', '');
        
        $this->assertEquals('dashboard_metrics_v2_1__', $result);
    }

    /** @test */
    public function remember_works_with_database_driver(): void
    {
        // Set config to database driver
        config(['cache.default' => 'database']);
        
        $service = new DashboardCacheService();
        $expectedResult = ['test' => 'data'];
        
        // We can't easily test Cache::remember with real cache in unit test,
        // but we can verify it doesn't throw exceptions
        $result = $service->remember(1, '2026-05-01', '2026-05-31', function () use ($expectedResult) {
            return $expectedResult;
        });
        
        $this->assertEquals($expectedResult, $result);
    }

    /** @test */
    public function invalidation_with_non_database_driver_calls_cache_flush(): void
    {
        // Set config to array driver (not database)
        config(['cache.default' => 'array']);
        
        // Mock Cache facade to expect flush
        \Illuminate\Support\Facades\Cache::shouldReceive('flush')
            ->once();
        
        $service = new DashboardCacheService();
        $service->clearAllForUser(1);
    }

    /** @test */
    public function invalidation_with_invalid_user_id_does_not_throw(): void
    {
        $service = new DashboardCacheService();
        
        // Should not throw even with invalid user ID
        try {
            $service->invalidateForUser(999999);
            $service->invalidateForUserAndDate(999999, '2026-05-01');
            $service->clearAllForUser(999999);
            $this->assertTrue(true, 'No exceptions for invalid user ID');
        } catch (\Exception $e) {
            $this->fail('Should not throw for invalid user ID: ' . $e->getMessage());
        }
    }
}
