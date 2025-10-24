<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Ecommerce\Market;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DetectMarketFromHost
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        // Debug: log incoming host and start detection
        Log::debug('DetectMarketFromHost: start', ['host' => $host]);

        // Load excluded hosts from configuration. If the current host matches
        // an excluded entry, skip market detection and continue the request.
        $excluded = config('ecommerce.excluded_hosts', []);
        // If the current host matches any excluded pattern (supports wildcards via Str::is),
        // skip market detection and continue the request.
        foreach ((array) $excluded as $pattern) {
            if (Str::is($pattern, $host)) {
                Log::debug('DetectMarketFromHost: host is excluded, skipping market detection', ['host' => $host, 'pattern' => $pattern]);
                return $next($request);
            }
        }

        // Try to find a market matching the custom domain
        try {
            Log::debug('DetectMarketFromHost: querying market by custom_domain', ['host' => $host]);
            $market = Market::where('custom_domain', $host)->where('is_active', true)->first();
            if ($market) {
                Log::debug('DetectMarketFromHost: market found', ['market_id' => $market->id, 'market_slug' => $market->slug]);
                // Immediately dispatch to the MarketController index for this host.
                $controller = app(\App\Http\Controllers\Ecommerce\MarketController::class);
                return app()->call([$controller, 'index'], ['request' => $request, 'market' => $market]);
            } else {
                // If host was not excluded and we couldn't find a Market, return 404.
                Log::warning('DetectMarketFromHost: host not excluded and no matching market found - aborting 404', ['host' => $host]);
                abort(404);
            }
        } catch (\Exception $e) {
            Log::error('DetectMarketFromHost error: ' . $e->getMessage(), ['host' => $host]);
            abort(404, 'Market not found.');
        }

        return $next($request);
    }
}
