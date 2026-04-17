<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use App\Traits\HasNaturalModelSorting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MarketController extends Controller
{
    use HasNaturalModelSorting;

    /**
     * Display the market homepage with featured items and basic info.
     */
    public function index(Request $request, Market $market)
    {
        if (config('app.debug')) {
            Log::debug('MarketController@index: entered', ['host' => $request->getHost(), 'market_id' => $market->id ?? null, 'market_slug' => $market->slug ?? null]);
        }

        try {
            // Load the related shop and company for additional info
            $market->load(['shop.company']);

            // Verify that the market's shop exists and is accessible
            if (! $market->shop) {
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
                'totalItems' => $totalItemsCount,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Market index error: '.$e->getMessage(), [
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
            if (! $market->shop) {
                return response()->json(['error' => 'Market unavailable'], 503);
            }

            $perPage = 12;
            $page = $request->get('page', 1);
            $search = $request->get('search');
            $category = $request->get('category');
            $brand = $request->get('brand');
            $modelFilter = $request->get('model');
            $sort = $request->get('sort', 'default');
            $groupByModel = $request->get('group_by_model', false);

            // If grouping by model
            if ($groupByModel) {
                $models = $market->getGroupedModels($search, $perPage, $category, $brand, $sort, false, $modelFilter);

                return response()->json([
                    'data' => $models->items(),
                    'current_page' => $models->currentPage(),
                    'last_page' => $models->lastPage(),
                    'per_page' => $models->perPage(),
                    'total' => $models->total(),
                    'has_more_pages' => $models->hasMorePages(),
                    'grouped_by_model' => true,
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

            // Filter by model if provided
            if ($modelFilter) {
                $query->where('model', 'like', "%{$modelFilter}%");
            }

            // Get items first
            $items = $query->get();

            // Load market items for visibility filtering
            $marketItems = $market->marketItems()->whereIn('item_id', $items->pluck('id'))->get();
            $marketItemsMap = $marketItems->keyBy('item_id');

            // Filter items: exclude those with issues unless is_visible = true
            $filteredItems = $items->filter(function ($item) use ($marketItemsMap) {
                $marketItem = $marketItemsMap[$item->id] ?? null;
                $hasIssues = ! empty($item->issues) && $item->issues !== '{}';

                // If item has issues, only include if is_visible is explicitly true
                if ($hasIssues) {
                    return $marketItem && $marketItem->is_visible === true;
                }

                // If no issues, include it
                return true;
            });

            // Apply sorting to filtered results
            $sorted = match ($sort) {
                'price_low' => $filteredItems->sortBy('selling_price'),
                'price_high' => $filteredItems->sortByDesc('selling_price'),
                'name' => $filteredItems->sortBy('model'),
                default => $this->applyHierarchicalModelSorting($filteredItems),
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
                'grouped_by_model' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Market products API error: '.$e->getMessage(), [
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
            if (! $market->shop) {
                return response()->json(['error' => 'Market unavailable'], 503);
            }

            // Use unified method - includeHidden=false for public view (only visible items)
            $result = $market->getModelsWithVariants($model, false);

            if (! $result) {
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
            Log::error('Market model variants error: '.$e->getMessage(), [
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
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Use unified method - includeHidden=false for public view (only visible items)
            $result = $market->getModelsWithVariants($model, false);

            if (! $result) {
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
            Log::error('Market show model variants error: '.$e->getMessage(), [
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
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            $perPage = 24; // More items per page for list view
            $category = $request->get('category');
            $brand = $request->get('brand');
            $modelFilter = $request->get('model');
            $sort = $request->get('sort', 'default'); // default, price_low, price_high
            $search = $request->get('search'); // Search query
            // Default to grouped view mode
            $groupByModel = $request->get('group_by_model', true);

            // Get available categories for filtering
            $categories = $market->getAvailableCategories();
            $availableModels = $market->getAvailableModels($brand);

            // Get market stats
            $stats = $market->getStats();

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            // If grouping by model (default behavior)
            if ($groupByModel) {
                $initialItems = $market->getGroupedModels($search, $perPage, $category, $brand, $sort, false, $modelFilter)->items();
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

                // Filter by model if provided
                if ($modelFilter) {
                    $query->where('model', 'like', "%{$modelFilter}%");
                }

                // Get items first
                $items = $query->get();

                // Load market items for visibility filtering
                $marketItems = $market->marketItems()->whereIn('item_id', $items->pluck('id'))->get();
                $marketItemsMap = $marketItems->keyBy('item_id');

                // Filter items: exclude those with issues unless is_visible = true
                $filteredItems = $items->filter(function ($item) use ($marketItemsMap) {
                    $marketItem = $marketItemsMap[$item->id] ?? null;
                    $hasIssues = ! empty($item->issues) && $item->issues !== '{}';

                    // If item has issues, only include if is_visible is explicitly true
                    if ($hasIssues) {
                        return $marketItem && $marketItem->is_visible === true;
                    }

                    // If no issues, include it
                    return true;
                });

                // Apply hierarchical sorting if default, otherwise use requested sort
                $sortedItems = match ($sort) {
                    'price_low' => $filteredItems->sortBy('selling_price'),
                    'price_high' => $filteredItems->sortByDesc('selling_price'),
                    'name' => $filteredItems->sortBy('model'),
                    default => $this->applyHierarchicalModelSorting($filteredItems),
                };

                // Get initial items for infinite scroll (first page only)
                $initialItems = $sortedItems->take($perPage)->values();
            }

            return Inertia::render('Ecommerce/PublicMarket/ProductsList', [
                'market' => $safeMarketData,
                'initialItems' => $initialItems,
                'categories' => $categories->values(),
                'availableModels' => $availableModels,
                'stats' => $stats,
                'currentCategory' => $category,
                'currentBrand' => $brand,
                'currentSort' => $sort,
                'currentSearch' => $search,
                'filters' => [
                    'category' => $category,
                    'brand' => $brand,
                    'sort' => $sort,
                    'search' => $search,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Market products list error: '.$e->getMessage(), [
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
            if (! $market->shop) {
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
            Log::error('Market category error: '.$e->getMessage(), [
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
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Ensure the item belongs to this market's shop
            if (config('app.debug')) {
                Log::info('Market product check', [
                    'market_shop_id' => $market->shop_id ?? null,
                    'item_shop_id' => $item->shop_id ?? null,
                ]);
            }
            if ($item->shop_id !== $market->shop_id) {
                abort(404, 'Product not found in this market');
            }

            // Ensure the item is available for sale
            if ($item->sold || $item->hold || ! $item->selling_price || $item->selling_price <= 0) {
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
                'relatedItems' => $relatedItems,
            ]);
        } catch (\Exception $e) {
            Log::error('Market product error: '.$e->getMessage(), [
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

        if (! empty($query)) {
            $items = $items->where(function ($q) use ($query) {
                $q->where('model', 'like', "%{$query}%")
                    ->orWhere('manufacturer', 'like', "%{$query}%")
                    ->orWhere('issues', 'like', "%{$query}%")
                    ->orWhere('imei', 'like', "%{$query}%");
            });
        }

        if (! empty($category)) {
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
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/Contact', [
                'market' => $safeMarketData,
            ]);
        } catch (\Exception $e) {
            Log::error('Market contact error: '.$e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            abort(503, 'Contact page is temporarily unavailable. Please try again later.');
        }
    }

    /**
     * Display the About Us page
     */
    public function about(Market $market)
    {
        try {
            $market->load(['shop']);

            // Verify shop accessibility
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            // Get About Us data from market (we'll assume a field exists or just use a default for now)
            $aboutData = $market->about_us ?? [
                'title' => 'About Us',
                'content' => 'Welcome to '.$market->name.'. We are dedicated to providing the best refurbished devices.',
                'image_url' => null,
            ];

            return Inertia::render('Ecommerce/PublicMarket/About', [
                'market' => $safeMarketData,
                'aboutData' => $aboutData,
            ]);
        } catch (\Exception $e) {
            Log::error('Market About Us error: '.$e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            abort(503, 'About Us page is temporarily unavailable. Please try again later.');
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
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            // Get FAQ data from market, default to empty structure if not set
            $faqData = $market->faq ?? [
                'title' => 'Frequently Asked Questions',
                'description' => '',
                'questions' => [],
            ];

            return Inertia::render('Ecommerce/PublicMarket/Faq', [
                'market' => $safeMarketData,
                'faqData' => $faqData,
            ]);
        } catch (\Exception $e) {
            Log::error('Market FAQ error: '.$e->getMessage(), [
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
            if (! $market->shop) {
                abort(503, 'This market is temporarily unavailable');
            }

            // Get safe market data
            $safeMarketData = $market->getSafeData();

            return Inertia::render('Ecommerce/PublicMarket/OrderReview', [
                'market' => $safeMarketData,
            ]);
        } catch (\Exception $e) {
            Log::error('Market cart error: '.$e->getMessage(), [
                'market_id' => $market->id ?? null,
                'market_slug' => $market->slug ?? null,
            ]);

            abort(503, 'Cart page is temporarily unavailable. Please try again later.');
        }
    }

    /**
     * Display order confirmation page
     */
    public function orderConfirmation(Request $request, Market $market, $sale_id)
    {
        try {
            // Find sale ignoring global scopes (like CompanyUsersSharedScope)
            // This is safe because we use 'signed' middleware and check ownership below
            $sale = \App\Models\Sale::withoutGlobalScopes()->findOrFail($sale_id);

            // Security check: Ensure the sale belongs to this market's shop company owner
            $market->load(['shop.company']);

            // The sale's user_id is the owner of the company (from CheckoutController)
            // The market's shop belongs to a company, which has an owner_id.
            $ownerId = $market->shop->company->owner_id;

            if ($sale->user_id !== $ownerId) {
                // Debugging log before aborting
                Log::warning('Unauthorized order access attempt', [
                    'sale_id' => $sale->id,
                    'sale_user_id' => $sale->user_id,
                    'market_owner_id' => $ownerId,
                    'market_shop_user_id' => $market->shop->user_id ?? 'N/A', // Old logic check
                ]);
                abort(403, 'Unauthorized access to order details.');
            }

            // Load sale items and details
            $sale->load(['items.media']);

            // Parse customer info from notes if available
            $customerName = $sale->customer;
            $customerDetails = json_decode($sale->notes, true);

            return Inertia::render('Ecommerce/PublicMarket/OrderConfirmation', [
                'market' => $market->getSafeData(),
                'order' => [
                    'id' => $sale->id,
                    'customer' => $customerName,
                    'customer_details' => $customerDetails,
                    'total' => $sale->total,
                    'subtotal' => $sale->subtotal,
                    'tax' => $sale->tax,
                    'date' => $sale->date->format('F j, Y, g:i a'),
                    'items' => $sale->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'model' => $item->model,
                            'manufacturer' => $item->manufacturer,
                            'price' => $item->selling_price,
                            'imei' => $item->imei,
                            'image_url' => $item->getFirstMediaUrl('item-photos', 'thumb'),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Order confirmation error: '.$e->getMessage());
            abort(404, 'Order not found');
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
            ],
        ]);
    }
}
