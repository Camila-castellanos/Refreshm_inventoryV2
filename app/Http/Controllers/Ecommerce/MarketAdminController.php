<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarketAdminController extends Controller
{
    /**
     * Display a listing of markets for the current user's company
     */
    public function index()
    {
        $markets = Market::with(['shop.company'])
            ->whereHas('shop', function ($query) {
                $query->where('company_id', Auth::user()->currentTeam->id);
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
        $shops = Shop::where('company_id', Auth::user()->currentTeam->id)
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shop_id' => [
                'required',
                'exists:shops,id',
                Rule::exists('shops', 'id')->where(function ($query) {
                    $query->where('company_id', Auth::user()->currentTeam->id);
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'tagline' => 'nullable|string|max:255',
            'currency' => 'required|string|in:USD,EUR,GBP,CAD,AUD',
            'show_inventory_count' => 'boolean',
            'is_active' => 'boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        // Generate unique slug
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Market::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Create the market
        $market = Market::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'shop_id' => $validated['shop_id'],
            'description' => $validated['description'],
            'tagline' => $validated['tagline'],
            'currency' => $validated['currency'],
            'show_inventory_count' => $validated['show_inventory_count'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'address' => $validated['address'],
            'meta_title' => $validated['meta_title'] ?: ($validated['name'] . ' - Online Market'),
            'meta_description' => $validated['meta_description'] ?: ('Browse and shop ' . $validated['name'] . ' collection of quality products.'),
        ]);

        return redirect()
            ->route('ecommerce.admin.markets.index')
            ->with('success', 'Market created successfully! You can now visit it at: ' . route('ecommerce.index', $market->slug));
    }

    /**
     * Show the form for editing a market
     */
    public function edit(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->currentTeam->id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $market->load(['shop']);

        // Get shops belonging to the current user's company
        $shops = Shop::where('company_id', Auth::user()->currentTeam->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Ecommerce/MarketEdit', [
            'market' => $market,
            'shops' => $shops,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Update the specified market
     */
    public function update(Request $request, Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->currentTeam->id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shop_id' => [
                'required',
                'exists:shops,id',
                Rule::exists('shops', 'id')->where(function ($query) {
                    $query->where('company_id', Auth::user()->currentTeam->id);
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'tagline' => 'nullable|string|max:255',
            'currency' => 'required|string|in:USD,EUR,GBP,CAD,AUD',
            'show_inventory_count' => 'boolean',
            'is_active' => 'boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        // Update slug if name changed
        if ($market->name !== $validated['name']) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;

            while (Market::where('slug', $slug)->where('id', '!=', $market->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        // Set defaults for meta fields if empty
        if (empty($validated['meta_title'])) {
            $validated['meta_title'] = $validated['name'] . ' - Online Market';
        }

        if (empty($validated['meta_description'])) {
            $validated['meta_description'] = 'Browse and shop ' . $validated['name'] . ' collection of quality products.';
        }

        $market->update($validated);

        return redirect()
            ->route('ecommerce.admin.markets.index')
            ->with('success', 'Market updated successfully!');
    }

    /**
     * Remove the specified market
     */
    public function destroy(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->currentTeam->id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $marketName = $market->name;
        $market->delete();

        return redirect()
            ->route('ecommerce.admin.markets.index')
            ->with('success', "Market '{$marketName}' deleted successfully.");
    }

    /**
     * Display market analytics and statistics
     */
    public function analytics(Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->currentTeam->id) {
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
        if ($market->shop->company_id !== Auth::user()->currentTeam->id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $market->update([
            'is_active' => !$market->is_active
        ]);

        $status = $market->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Market {$status} successfully.");
    }
}