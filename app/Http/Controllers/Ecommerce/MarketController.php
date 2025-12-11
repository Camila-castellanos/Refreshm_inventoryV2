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
        Log::debug('MarketController@index: entered', ['host' => $request->getHost(), 'market_id' => $market->id ?? null, 'market_slug' => $market->slug ?? null]);

        try {
            // Load the related shop and company for additional info
            $market->load(['shop.company']);

            // Verify that the market's shop exists and is accessible
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get featured items grouped by model (take 8 models)
            $groupedModels = $market->getGroupedModels(null, 8);
            // Extract items from paginator
            $featuredItems = $groupedModels->items();

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
                'initialItems' => $featuredItems, // Featured models grouped
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
     * API endpoint for infinite scroll - Load more products grouped by model
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
            $search = $request->get('search');
            $category = $request->get('category');
            $brand = $request->get('brand');
            $sort = $request->get('sort', 'latest');
            $groupByModel = $request->get('group_by_model', false);

            // If grouping by model
            if ($groupByModel) {
                $models = $market->getGroupedModels($search, $perPage, $category, $brand, $sort);

                return response()->json([
                    'data' => $models->items(),
                    'current_page' => $models->currentPage(),
                    'last_page' => $models->lastPage(),
                    'per_page' => $models->perPage(),
                    'total' => $models->total(),
                    'has_more_pages' => $models->hasMorePages(),
                    'grouped_by_model' => true
                ]);
            }

            // Original item-by-item pagination
            // Build query
            $query = $market->publishedItems();

            // Filter by search query if provided
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('model', 'like', "%{$search}%")
                      ->orWhere('manufacturer', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhere('imei', 'like', "%{$search}%");
                });
            }

            // Filter by category if provided
            if ($category) {
                $query->where('type', $category);
            }

            // Filter by brand if provided
            if ($brand) {
                $query->where('manufacturer', $brand);
            }

            // Get items first
            $items = $query->get();

            // Load market items for visibility filtering
            $marketItems = $market->marketItems()->whereIn('item_id', $items->pluck('id'))->get();
            $marketItemsMap = $marketItems->keyBy('item_id');

            // Filter items: exclude those with issues unless is_visible = true
            $filteredItems = $items->filter(function ($item) use ($marketItemsMap) {
                $marketItem = $marketItemsMap[$item->id] ?? null;
                $hasIssues = !empty($item->issues) && $item->issues !== '{}';

                // If item has issues, only include if is_visible is explicitly true
                if ($hasIssues) {
                    return $marketItem && $marketItem->is_visible === true;
                }

                // If no issues, include it
                return true;
            });

            // Apply sorting to filtered results
            $sorted = match($sort) {
                'price_low' => $filteredItems->sortBy('selling_price'),
                'price_high' => $filteredItems->sortByDesc('selling_price'),
                'name' => $filteredItems->sortBy('model'),
                default => $filteredItems->reverse(), // latest first
            };

            // Paginate manually
            $perPage = 12;
            $page = $request->get('page', 1);
            $total = $sorted->count();
            $paginatedItems = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

            // Create Laravel paginator
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedItems,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json([
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more_pages' => $paginator->hasMorePages(),
                'grouped_by_model' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Market products API error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'page' => $request->get('page'),
                'category' => $request->get('category'),
                'brand' => $request->get('brand'),
                'sort' => $request->get('sort'),
                'search' => $request->get('search'),
            ]);

            return response()->json(['error' => 'Unable to load products'], 500);
        }
    }

    /**
     * Get model variants - API endpoint for getting details of grouped model
     */
    public function modelVariants(Request $request, Market $market, $model)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                return response()->json(['error' => 'Market unavailable'], 503);
            }

            // Use unified method - includeHidden=false for public view (only visible items)
            $result = $market->getModelsWithVariants($model, false);

            if (!$result) {
                return response()->json(['error' => 'Model not found'], 404);
            }

            // Also get the structured variants for the dropdown selectors
            $variants = $market->getModelVariants($model);

            return response()->json([
                'model' => $result['model'],
                'manufacturer' => $result['manufacturer'],
                'type' => $result['type'],
                'items' => $result['items'],
                'variants' => $variants ? $variants['variants'] : [],
                'total_stock' => count($result['items']),
            ]);

        } catch (\Exception $e) {
            Log::error('Market model variants error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'model' => $model,
            ]);

            return response()->json(['error' => 'Unable to load variants'], 500);
        }
    }

    /**
     * Display model variants page with dropdowns for filtering (Public view)
     */
    public function showModelVariants(Request $request, Market $market, $model)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Use unified method - includeHidden=false for public view (only visible items)
            $result = $market->getModelsWithVariants($model, false);

            if (!$result) {
                abort(404, 'Model not found');
            }

            // Also get the structured variants for the dropdown selectors
            $variants = $market->getModelVariants($model);

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/ModelVariants', [
                'market' => $safeMarketData,
                'modelData' => [
                    'model' => $result['model'],
                    'manufacturer' => $result['manufacturer'],
                    'type' => $result['type'],
                    'items' => $result['items'],
                    'variants' => $variants ? $variants['variants'] : [],
                    'total_stock' => count($result['items']),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Market show model variants error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'model' => $model,
            ]);

            abort(503, 'Unable to load model details. Please try again later.');
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
            $search = $request->get('search'); // Search query
            // Default to grouped view mode
            $groupByModel = $request->get('group_by_model', true);

            // Get available categories for filtering
            $categories = $market->getAvailableCategories();

            // Get market stats
            $stats = $market->getStats();

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            // If grouping by model (default behavior)
            if ($groupByModel) {
                $initialItems = $market->getGroupedModels($search, $perPage, $category, $brand, $sort)->items();
            } else {
                // Build query for individual items
                $query = $market->publishedItems();

                // Filter by search query if provided
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('model', 'like', "%{$search}%")
                          ->orWhere('manufacturer', 'like', "%{$search}%")
                          ->orWhere('type', 'like', "%{$search}%")
                          ->orWhere('imei', 'like', "%{$search}%");
                    });
                }

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

                // Get items first
                $items = $query->take($perPage * 2)->get(); // Get more to account for filtering

                // Load market items for visibility filtering
                $marketItems = $market->marketItems()->whereIn('item_id', $items->pluck('id'))->get();
                $marketItemsMap = $marketItems->keyBy('item_id');

                // Filter items: exclude those with issues unless is_visible = true
                $filteredItems = $items->filter(function ($item) use ($marketItemsMap) {
                    $marketItem = $marketItemsMap[$item->id] ?? null;
                    $hasIssues = !empty($item->issues) && $item->issues !== '{}';

                    // If item has issues, only include if is_visible is explicitly true
                    if ($hasIssues) {
                        return $marketItem && $marketItem->is_visible === true;
                    }

                    // If no issues, include it
                    return true;
                });

                // Get initial items for infinite scroll (first page only)
                $initialItems = $filteredItems->take($perPage)->values();
            }

            return Inertia::render('Ecommerce/PublicMarket/ProductsList', [
                'market' => $safeMarketData,
                'initialItems' => $initialItems,
                'categories' => $categories->values(),
                'stats' => $stats,
                'currentCategory' => $category,
                'currentBrand' => $brand,
                'currentSort' => $sort,
                'currentSearch' => $search,
                'filters' => [
                    'category' => $category,
                    'brand' => $brand,
                    'sort' => $sort,
                    'search' => $search
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Market products list error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'category' => $category ?? null,
                'brand' => $brand ?? null,
                'sort' => $sort ?? null,
                'search' => $search ?? null,
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
     * Display the FAQ page
     */
    public function faq(Market $market)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            // Get FAQ data from market, default to empty structure if not set
            $faqData = $market->faq ?? [
                'title' => 'Frequently Asked Questions',
                'description' => '',
                'questions' => []
            ];

            return Inertia::render('Ecommerce/PublicMarket/Faq', [
                'market' => $safeMarketData,
                'faqData' => $faqData
            ]);
        } catch (\Exception $e) {
            Log::error('Market FAQ error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            abort(503, 'FAQ page is temporarily unavailable. Please try again later.');
        }
    }

    /**
     * Display the cart review page
     */
    public function cart(Market $market)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (!$market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/OrderReview', [
                'market' => $safeMarketData
            ]);
        } catch (\Exception $e) {
            Log::error('Market cart error: ' . $e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            abort(503, 'Cart page is temporarily unavailable. Please try again later.');
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