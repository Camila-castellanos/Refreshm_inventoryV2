<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIPService
{
    /**
     * Map an IP address to a currency code.
     * US -> USD, all others -> CAD.
     *
     * @param string $ip
     * @return string
     */
    public function getCurrencyByIP(string $ip): string
    {
        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();
                $countryCode = $data['country_code'] ?? null;

                if ($countryCode === 'US') {
                    return 'USD';
                }
            }
        } catch (\Exception $e) {
            Log::error("GeoIP detection failed for IP {$ip}: " . $e->getMessage());
        }

        return 'CAD';
    }
}
