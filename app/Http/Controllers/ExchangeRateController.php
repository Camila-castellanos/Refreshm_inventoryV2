<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    private const CACHE_KEY = 'exchange_rate_usd_cad';
    private const CACHE_DURATION_MINUTES = 60;
    private const API_BASE_URL = 'https://v6.exchangerate-api.com/v6';
    private const MARKUP_PERCENTAGE = 0.05; // 5% markup

    /**
     * Get the current exchange rate with fallback API keys
     * Uses server-side cache to avoid saturating the API
     * 
     * @return JsonResponse
     */
    public function getExchangeRate(): JsonResponse
    {
        try {
            // Try to get from cache first
            $cachedRate = Cache::get(self::CACHE_KEY);
            if ($cachedRate !== null) {
                return response()->json([
                    'success' => true,
                    'rate' => $cachedRate,
                    'cached' => true,
                    'message' => 'Rate retrieved from cache'
                ]);
            }

            // Fetch from API with fallback keys
            $rate = $this->fetchExchangeRateFromAPI();

            if ($rate === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'All API keys have reached their limit or failed'
                ], 429);
            }

            // Cache the rate
            Cache::put(self::CACHE_KEY, $rate, now()->addMinutes(self::CACHE_DURATION_MINUTES));

            return response()->json([
                'success' => true,
                'rate' => $rate,
                'cached' => false,
                'message' => 'Rate fetched from API and cached'
            ]);
        } catch (\Exception $e) {
            Log::error('Exchange rate error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching exchange rate'
            ], 500);
        }
    }

    /**
     * Fetch exchange rate from API with fallback API keys
     * 
     * @param int $retryIndex
     * @return float|null
     */
    private function fetchExchangeRateFromAPI(int $retryIndex = 0): ?float
    {
        $apiKeys = config('services.exchange_rate_api_keys', [
            '90fafe5c1eca42cafc565fb7',
            '39e80064a26cbd018ebae95a',
            '5b6c177ba8a448de34cdda38'
        ]);

        if ($retryIndex >= count($apiKeys)) {
            Log::error('All API keys exhausted for exchange rate');
            return null;
        }

        $apiKey = $apiKeys[$retryIndex];
        $url = self::API_BASE_URL . '/' . $apiKey . '/latest/USD/';

        try {
            $response = Http::timeout(10)->get($url);

            // Handle 429 Too Many Requests
            if ($response->status() === 429) {
                Log::warning("Exchange Rate API key {$retryIndex} reached rate limit. Trying next key...");
                return $this->fetchExchangeRateFromAPI($retryIndex + 1);
            }

            if (!$response->successful()) {
                throw new \Exception("API error: {$response->status()} {$response->reason()}");
            }

            $data = $response->json();
            $conversionRates = $data['conversion_rates'] ?? null;

            if ($conversionRates && isset($conversionRates['CAD'])) {
                $rate = (float) $conversionRates['CAD'];
                // Apply 3% markup
                $rate -= ($rate * self::MARKUP_PERCENTAGE);
                
                Log::info("Exchange rate fetched successfully using API key " . ($retryIndex + 1));
                return $rate;
            }

            throw new \Exception('CAD rate not found in response');
        } catch (\Exception $e) {
            Log::error("Exchange Rate API error with key {$retryIndex}: " . $e->getMessage());

            // Try next API key if available
            if ($retryIndex < count($apiKeys) - 1) {
                return $this->fetchExchangeRateFromAPI($retryIndex + 1);
            }

            return null;
        }
    }

    /**
     * Force refresh the exchange rate (bypass cache)
     * 
     * @return JsonResponse
     */
    public function refreshExchangeRate(): JsonResponse
    {
        try {
            Cache::forget(self::CACHE_KEY);
            
            $rate = $this->fetchExchangeRateFromAPI();

            if ($rate === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'All API keys have reached their limit or failed'
                ], 429);
            }

            Cache::put(self::CACHE_KEY, $rate, now()->addMinutes(self::CACHE_DURATION_MINUTES));

            return response()->json([
                'success' => true,
                'rate' => $rate,
                'message' => 'Exchange rate refreshed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Exchange rate refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing exchange rate'
            ], 500);
        }
    }

    /**
     * Clear the exchange rate cache
     * 
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget(self::CACHE_KEY);
        return response()->json([
            'success' => true,
            'message' => 'Exchange rate cache cleared'
        ]);
    }
}
