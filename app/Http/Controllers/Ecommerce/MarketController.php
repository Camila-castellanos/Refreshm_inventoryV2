<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketController extends Controller
{
    /**
     * Display the market homepage with featured items and basic info.
     */
    public function index(Market $market)
    {
        try {
            // Load the related shop and company for additional info
            $market->load(['shop.company']);

            // Verify that the market's shop exists and is accessible
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get featured items (latest available items)
            $featuredItems = $market->featuredItems(8)->get();

            // Get total available items count
            $totalItemsCount = $market->publishedItems()->count();

            // Get distinct categories for navigation
            $categories = $market->getAvailableCategories();

            // Get market stats
            $stats = $market->getStats();

            // Get safe market data with fallbacks
            $safeMarketData = $market->getSafeData();

            return view('ecommerce.market.index', compact(
                'market',
                'featuredItems',
                'totalItemsCount',
                'categories',
                'stats',
                'safeMarketData'
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Market index error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            // Return a user-friendly error page
            abort(503, 'This market is temporarily unavailable. Please try again later.');
        }
    }

    /**
     * Display items by category
     */
    public function category(Market $market, string $category)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            $items = $market->getItemsByCategory($category)
                ->paginate(12);

            $categoryInfo = [
                'name' => $category,
                'displayName' => ucfirst($category),
                'totalItems' => $market->publishedItems()->where('type', $category)->count(),
            ];

            return view('ecommerce.market.category', compact(
                'market', 
                'items', 
                'categoryInfo'
            ));
        } catch (\Exception $e) {
            Log::error('Market category error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'category' => $category,
            ]);

            abort(503, 'Unable to load category products. Please try again later.');
        }
    }

    /**
     * Display a single product page
     */
    public function product(Market $market, Item $item)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Ensure the item belongs to this market's shop
            if ($item->shop_id !== $market->shop_id) {
                abort(404, 'Product not found in this market');
            }

            // Ensure the item is available for sale
            if ($item->sold || $item->hold || !$item->selling_price || $item->selling_price <= 0) {
                abort(404, 'Product not available');
            }

            // Load relationships
            $item->load(['vendor']);

            // Get related items (same type, different items)
            $relatedItems = $market->getItemsByCategory($item->type ?? 'general')
                ->where('id', '!=', $item->id)
                ->limit(4)
                ->get();

            return view('ecommerce.market.product', compact(
                'market',
                'item',
                'relatedItems'
            ));
        } catch (\Exception $e) {
            Log::error('Market product error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'item_id' => $item->id ?? null,
            ]);

            abort(404, 'Product not found or unavailable');
        }
    }

    /**
     * Search products in the market
     */
    public function search(Request $request, Market $market)
    {
        $query = $request->get('q', '');
        $category = $request->get('category', '');

        $items = $market->publishedItems();

        if (!empty($query)) {
            $items = $items->where(function ($q) use ($query) {
                $q->where('model', 'like', "%{$query}%")
                  ->orWhere('manufacturer', 'like', "%{$query}%")
                  ->orWhere('issues', 'like', "%{$query}%")
                  ->orWhere('imei', 'like', "%{$query}%");
            });
        }

        if (!empty($category)) {
            $items = $items->where('type', $category);
        }

        $items = $items->with(['vendor'])->paginate(12);

        return view('ecommerce.market.search', compact(
            'market',
            'items',
            'query',
            'category'
        ));
    }

    /**
     * Get market info for API
     */
    public function info(Market $market)
    {
        $market->load(['shop.company']);
        
        return response()->json([
            'market' => [
                'name' => $market->name,
                'description' => $market->description,
                'tagline' => $market->tagline,
                'currency' => $market->currency,
                'stats' => $market->getStats(),
                'contact' => [
                    'email' => $market->contact_email,
                    'phone' => $market->contact_phone,
                    'address' => $market->address,
                ],
                'company' => [
                    'name' => $market->shop->company->name ?? null,
                ],
            ]
        ]);
    }
}