<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Inertia\Inertia;
use App\Models\Shop; // Make sure Shop model is imported
use App\Models\Company; // Potentially needed if accessing company directly
use App\Models\Tab; // Import Tab model for user tabs
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventoryPublicController extends Controller
{
    /**
     * Display a listing of the resource for a specific shop.
     * Uses shop slug for lookup with fallback to ID.
     */
    public function index(string $shopSlug): \Inertia\Response
    {
        $shop = null; // Initialize $shop
        $userTabs = []; // Initialize user tabs
        
        try {
            // First try to find by slug
            $shop = Shop::where('slug', $shopSlug)->with(['company.owner'])->first();
            
            // If not found and shopSlug looks like an ID (numeric), try by ID
            if (!$shop && is_numeric($shopSlug)) {
                $shop = Shop::where('id', $shopSlug)->with(['company.owner'])->first();
            }
            
            // If still not found, try by name (final fallback)
            if (!$shop) {
                // Convert underscores back to spaces for name lookup
                $shopName = str_replace('_', ' ', $shopSlug);
                $shop = Shop::where('name', $shopName)->with(['company.owner'])->first();
            }
            
            if (!$shop) {
                abort(404);
            }

            // Get the owner's tabs if available
            if ($shop->company && $shop->company->owner) {
                // Get the owner's tabs ordered by their order field
                $userTabs = Tab::where('user_id', $shop->company->owner->id)
                    ->orderBy('order', 'asc')
                    ->get(['id', 'name', 'order'])
                    ->toArray();
            }

        } catch (\Exception $e) {
            abort(404);
        }

        $items = Item::withoutGlobalScopes()
            ->where("shop_id", $shop->id)
            // Option 1: Original - Assumes NULL means available
            ->whereNull("sold")
            ->whereNull("hold")
            ->whereNotNull('model')
            ->whereNotIn('id', \App\Models\TabItem::pluck('item_id')) // Exclude items in tabs
            ->get();


        return Inertia::render('PublicInventory/Index', [
            'items' => $items,
            'shopName' => $shop->name,
            'shopSlug' => $shop->slug,
            'userTabs' => $userTabs,
        ]);
    }

    /**
     * Get unique models filtered by manufacturers (public endpoint)
     * This is called from InventoryList.vue when filtering by brand
     */
    public function getUniqueModelsByManufacturer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manufacturers' => 'required|array',
            'manufacturers.*' => 'string',
        ]);

        $manufacturers = array_map(fn($m) => mb_strtolower(trim($m)), $data['manufacturers']);

        if (empty($manufacturers)) {
            return response()->json(['models' => []]);
        }

        // Query items active in inventory (not sold, not on hold) and matching manufacturers case-insensitively
        // Exclude items that are in custom tabs
        $models = Item::whereNull('sold')
            ->whereNull('hold')
            ->whereIn('type', ['device'])
            ->whereIn(DB::raw('LOWER(manufacturer)'), $manufacturers)
            ->whereNotIn('id', \App\Models\TabItem::pluck('item_id')) // Exclude items in tabs
            ->pluck('model')
            ->filter()
            ->values();

        // Normalize models: remove storage sizes like '128GB', parentheses content, punctuation and collapse whitespace
        $normalized = collect($models)->map(function ($model) {
            $m = trim((string)$model);
            // Remove parenthetical notes: "(Unlocked)", etc.
            $m = preg_replace('/\(.+?\)/u', ' ', $m);
            // Remove storage sizes like '128GB', '256 GB', case-insensitive
            $m = preg_replace('/\b\d+\s*gb\b/iu', ' ', $m);
            // Remove other common capacity notations like '128gb', '128 g', '128g'
            $m = preg_replace('/\b\d+\s*g\b/iu', ' ', $m);
            // Replace any non-alphanumeric (except spaces) with space
            $m = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $m);
            // Collapse multiple spaces into one
            $m = preg_replace('/\s+/u', ' ', $m);
            $m = trim($m);
            return mb_strtolower($m);
        })->filter()->unique()->values()->sort()->map(function ($m) {
            return ['label' => mb_convert_case($m, MB_CASE_TITLE, 'UTF-8'), 'value' => $m];
        })->values()->all();

        return response()->json(['models' => $normalized]);
    }
    /*
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not implemented for public view
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Not implemented for public view
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Could implement to show a single item detail page
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Not implemented for public view
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Not implemented for public view
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Not implemented for public view
    }
}