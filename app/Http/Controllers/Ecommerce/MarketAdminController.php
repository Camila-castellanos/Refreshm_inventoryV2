<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketForm;
use App\Models\Ecommerce\Market;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MarketAdminController extends Controller
{
    /**
     * Display a listing of markets for the current user's company
     */
    public function index()
    {
        $markets = Market::with(['shop.company'])
            ->whereHas('shop', function ($query) {
                $query->where('company_id', Auth::user()->company_id);
            })
            ->withCount(['publishedItems'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Ecommerce/MarketIndex', [
            'markets' => $markets,
        ]);
    }

    /**
     * Show the form for creating a new market
     */
    public function create()
    {
        // Get shops belonging to the current user's company
        $shops = Shop::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ecommerce/MarketCreation', [
            'shops' => $shops,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Store a newly created market
     */
    public function store(MarketForm $request)
    {
        $validated = $request->validated();

        // Generate unique slug
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Market::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Create the market
        $market = Market::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'shop_id' => $validated['shop_id'],
            'custom_domain' => $validated['custom_domain'] ?? null,
            'description' => $validated['description'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'currency' => $validated['currency'],
            'show_inventory_count' => $validated['show_inventory_count'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'meta_title' => $validated['meta_title'] ?? ($validated['name'].' - Online Market'),
            'meta_description' => $validated['meta_description'] ?? ('Browse and shop '.$validated['name'].' collection of quality products.'),
            'faq' => $validated['faq'] ?? null,
        ]);

        // Handle Banner Uploads
        if ($request->hasFile('banners')) {
            foreach ($request->file('banners') as $banner) {
                $market->addMedia($banner)
                    ->toMediaCollection('banners');
            }
        }

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            $market->addMediaFromRequest('logo')
                ->toMediaCollection('logo');
        }

        // Build the public market URL
        $publicMarketUrl = route('market.index', $market->slug);

        return redirect()
            ->route('ecommerce.markets.index')
            ->with('success', 'Market created successfully! Public URL: '.$publicMarketUrl);
    }

    /**
     * Show the form for editing a market
     */
    public function edit(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $market->load(['shop']);

        // Get shops belonging to the current user's company
        $shops = Shop::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ecommerce/MarketEdit', [
            'market' => $market->getSafeData(),
            'shops' => $shops,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Update the specified market
     */
    public function update(MarketForm $request, Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $validated = $request->validated();

        // Log incoming request data for debugging
        Log::info('Market update request received', [
            'market_id' => $market->id,
            'has_banners' => $request->hasFile('banners'),
            'banner_count' => $request->hasFile('banners') ? count($request->file('banners')) : 0,
            'has_deleted_banners' => $request->has('deleted_banners'),
            'deleted_banners' => $request->input('deleted_banners', []),
        ]);

        // Update slug if name changed
        if ($market->name !== $validated['name']) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;

            while (Market::where('slug', $slug)->where('id', '!=', $market->id)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        // Set defaults for meta fields if empty
        if (empty($validated['meta_title'])) {
            $validated['meta_title'] = $validated['name'].' - Online Market';
        }

        if (empty($validated['meta_description'])) {
            $validated['meta_description'] = 'Browse and shop '.$validated['name'].' collection of quality products.';
        }

        // validate uniqueness for custom_domain on update (ignore current)
        if (isset($validated['custom_domain'])) {
            $validated['custom_domain'] = $validated['custom_domain'] ?: null;
        }

        $market->update($validated);

        // Handle Banner Uploads
        if ($request->hasFile('banners')) {
            foreach ($request->file('banners') as $banner) {
                $market->addMedia($banner)
                    ->toMediaCollection('banners');
            }
        }

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            $market->addMediaFromRequest('logo')
                ->toMediaCollection('logo');
        }

        // Handle Banner Removals
        if ($request->filled('deleted_banners')) {
            $deletedIds = $request->input('deleted_banners');

            Log::info('Processing banner deletions', [
                'market_id' => $market->id,
                'deleted_ids' => $deletedIds,
            ]);

            if (is_array($deletedIds) && count($deletedIds) > 0) {
                // Ensure IDs are integers
                $ids = array_map('intval', $deletedIds);

                // Fetch the media items first to ensure we can call delete() on the models
                // This triggers Spatie's events to delete the physical files
                $mediaItems = Media::whereIn('id', $ids)
                    ->where('model_type', get_class($market))
                    ->where('model_id', $market->id)
                    ->where('collection_name', 'banners')
                    ->get();

                Log::info('Found media items for deletion', [
                    'requested_count' => count($ids),
                    'found_count' => $mediaItems->count(),
                    'found_ids' => $mediaItems->pluck('id')->toArray(),
                ]);

                // Delete each item
                $deletedCount = 0;
                foreach ($mediaItems as $media) {
                    try {
                        $media->delete();
                        $deletedCount++;
                    } catch (\Exception $e) {
                        Log::error('Failed to delete media', [
                            'media_id' => $media->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::info('Banner deletion completed', [
                    'deleted_count' => $deletedCount,
                    'total_requested' => count($ids),
                ]);
            }
        }

        return redirect()
            ->route('ecommerce.markets.index')
            ->with('success', 'Market updated successfully!');
    }

    /**
     * Remove the specified market
     */
    public function destroy(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $marketName = $market->name;
        $market->delete();

        return redirect()
            ->route('ecommerce.markets.index')
            ->with('success', "Market '{$marketName}' deleted successfully.");
    }

    /**
     * Display market analytics and statistics
     */
    public function analytics(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $market->load(['shop']);

        $stats = [
            'total_products' => $market->publishedItems()->count(),
            'categories_count' => $market->getAvailableCategories()->count(),
            'products_by_category' => $market->publishedItems()
                ->select('type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('type')
                ->whereNotNull('type')
                ->get()
                ->pluck('count', 'type'),
            'price_range' => [
                'min' => $market->publishedItems()->min('selling_price') ?? 0,
                'max' => $market->publishedItems()->max('selling_price') ?? 0,
                'avg' => $market->publishedItems()->avg('selling_price') ?? 0,
            ],
        ];

        return Inertia::render('Ecommerce/MarketAnalytics', [
            'market' => $market,
            'stats' => $stats,
        ]);
    }

    /**
     * Toggle market active status
     */
    public function toggleStatus(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $market->update([
            'is_active' => ! $market->is_active,
        ]);

        $status = $market->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('ecommerce.markets.index')
            ->with('success', "Market {$status} successfully.");
    }
}
