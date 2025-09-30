<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MarketController extends Controller
{
    /**
     * Display the market homepage with featured items and basic info.
     */
    public function index(Request $request, Market $market)
    {
        try {
            // Load the related shop and company for additional info
            $market->load(['shop.company']);

            // Verify that the market's shop exists and is accessible
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get only 8 featured items for the homepage
            $featuredItems = $market->featuredItems(8)->get();

            // Get total available items count
            $totalItemsCount = $market->publishedItems()->count();

            // Get distinct categories for navigation
            $categories = $market->getAvailableCategories();

            // Get market stats
            $stats = $market->getStats();

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/Home', [
                'market' => $safeMarketData,
                'initialItems' => $featuredItems, // Only 8 featured items for homepage
                'categories' => $categories->values(), // Reset array keys
                'stats' => $stats,
                'totalItems' => $totalItemsCount
            ]);
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
     * API endpoint for infinite scroll - Load more products
     */
    public function products(Request $request, Market $market)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                return response()->json(['error' => 'Market unavailable'], 503);
            }

            $perPage = 12;
            $page = $request->get('page', 1);
            $category = $request->get('category');
            $brand = $request->get('brand');
            $sort = $request->get('sort', 'latest');

            // Build query
            $query = $market->publishedItems();

            // Filter by category if provided
            if ($category) {
                $query->where('type', $category);
            }

            // Filter by brand if provided
            if ($brand) {
                $query->where('manufacturer', $brand);
            }

            // Apply sorting
            switch ($sort) {
                case 'price_low':
                    $query->orderBy('selling_price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('selling_price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('model', 'asc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }

            // Get paginated results
            $items = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $items->items(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'has_more_pages' => $items->hasMorePages()
            ]);

        } catch (\Exception $e) {
            Log::error('Market products API error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'page' => $request->get('page'),
                'category' => $request->get('category'),
                'brand' => $request->get('brand'),
                'sort' => $request->get('sort'),
            ]);

            return response()->json(['error' => 'Unable to load products'], 500);
        }
    }

    /**
     * Display a detailed products list page
     */
    public function productsList(Request $request, Market $market)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            $perPage = 24; // More items per page for list view
            $category = $request->get('category');
            $brand = $request->get('brand');
            $sort = $request->get('sort', 'latest'); // latest, price_low, price_high, name

            // Build query
            $query = $market->publishedItems();

            // Filter by category if provided
            if ($category) {
                $query->where('type', $category);
            }

            // Filter by brand if provided
            if ($brand) {
                $query->where('manufacturer', $brand);
            }

            // Apply sorting
            switch ($sort) {
                case 'price_low':
                    $query->orderBy('selling_price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('selling_price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('model', 'asc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }

            // Get initial items for infinite scroll (first page only)
            $initialItems = $query->take($perPage)->get();

            // Get available categories for filtering
            $categories = $market->getAvailableCategories();

            // Get market stats
            $stats = $market->getStats();

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/ProductsList', [
                'market' => $safeMarketData,
                'initialItems' => $initialItems, // Changed from items to initialItems
                'categories' => $categories->values(),
                'stats' => $stats,
                'currentCategory' => $category,
                'currentBrand' => $brand,
                'currentSort' => $sort,
                'filters' => [
                    'category' => $category,
                    'brand' => $brand,
                    'sort' => $sort
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Market products list error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'category' => $category,
                'brand' => $brand,
                'sort' => $sort,
            ]);

            abort(503, 'Products list is temporarily unavailable. Please try again later.');
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
            Log::info('Market product check', [
                'market_shop_id' => $market->shop_id ?? null,
                'item_shop_id' => $item->shop_id ?? null,
            ]);
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
                ->where('items.id', '!=', $item->id)
                ->limit(4)
                ->get();

            return Inertia::render('Ecommerce/PublicMarket/Product', [
                'market' => $market->getSafeData(),
                'item' => $item,
                'relatedItems' => $relatedItems
            ]);
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
     * Display the contact page
     */
    public function contact(Market $market)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/Contact', [
                'market' => $safeMarketData
            ]);
        } catch (\Exception $e) {
            Log::error('Market contact error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            abort(503, 'Contact page is temporarily unavailable. Please try again later.');
        }
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