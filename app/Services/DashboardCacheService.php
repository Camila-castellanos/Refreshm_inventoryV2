<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardCacheService
{
    /**
     * Generate cache key for dashboard metrics
     */
    public function getCacheKey(int $userId, string $startDate, string $endDate): string
    {
        return "dashboard_metrics_v2_{$userId}_{$startDate}_{$endDate}";
    }

    /**
     * Remember dashboard metrics with cache
     */
    public function remember(int $userId, string $startDate, string $endDate, callable $callback)
    {
        $key = $this->getCacheKey($userId, $startDate, $endDate);
        
        return Cache::remember($key, now()->addMinutes(30), $callback);
    }

    /**
     * Invalidate cache for a specific user
     */
    public function invalidateForUser(int $userId): void
    {
        $this->clearAllForUser($userId);
    }

    /**
     * Invalidate cache for a specific user and date
     */
    public function invalidateForUserAndDate(int $userId, string $date): void
    {
        // Delegate to clearAllForUser for simplicity as per design
        $this->clearAllForUser($userId);
    }

    /**
     * Clear all cache for a user (driver-aware)
     */
    public function clearAllForUser(int $userId): void
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'database') {
                $prefix = config('cache.prefix', '');
                $pattern = $prefix . 'dashboard_metrics_v2_' . $userId . '_%';
                
                DB::table('cache')
                    ->where('key', 'like', $pattern)
                    ->delete();
            } elseif ($driver === 'redis') {
                // For Redis, we would use Redis tags or scan/delete pattern
                // This is a placeholder for Redis implementation
                Cache::flush();
            } elseif ($driver === 'memcached') {
                // For Memcached, we would need to iterate and delete
                // This is a placeholder for Memcached implementation
                Cache::flush();
            } else {
                // For array or file driver, flush all cache
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::error('DashboardCacheService: Failed to invalidate cache for user ' . $userId . ': ' . $e->getMessage());
        }
    }
}
