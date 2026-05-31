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
        // For local development, try to get the public IP if we get 127.0.0.1
        if ($this->isLocalIP($ip)) {
            try {
                $publicIpResponse = Http::timeout(2)->get('https://api.ipify.org?format=json');
                if ($publicIpResponse->successful()) {
                    $ip = $publicIpResponse->json()['ip'] ?? $ip;
                }
            } catch (\Exception $e) {
                // Silently fail and use original IP
            }
        }

        try {
            // Service 1: ipapi.co (with 1k/day free limit)
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();
                $countryCode = $data['country_code'] ?? null;

                if ($countryCode) {
                    return $countryCode === 'US' ? 'USD' : 'CAD';
                }
            }
        } catch (\Exception $e) {
            // Log for monitoring but continue to fallback
            Log::warning("GeoIP (ipapi.co) failed for IP {$ip}: " . $e->getMessage());
        }

        try {
            // Service 2: ip-api.com (Fallback - free for non-commercial/dev)
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                $countryCode = $data['countryCode'] ?? null;

                if ($countryCode) {
                    return $countryCode === 'US' ? 'USD' : 'CAD';
                }
            }
        } catch (\Exception $e) {
            Log::error("GeoIP Fallback (ip-api.com) failed for IP {$ip}: " . $e->getMessage());
        }

        return 'CAD';
    }

    /**
     * Check if the IP is local or private.
     */
    private function isLocalIP(string $ip): bool
    {
        return $ip === '127.0.0.1' || $ip === '::1' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
