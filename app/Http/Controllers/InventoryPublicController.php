<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Inertia\Inertia;
use App\Models\Shop; // Make sure Shop model is imported
use App\Models\Company; // Potentially needed if accessing company directly

class InventoryPublicController extends Controller
{
    /**
     * Display a listing of the resource for a specific shop.
     * Uses shop slug for lookup with fallback to ID.
     */
    public function index(string $shopSlug): \Inertia\Response
    {
        $shop = null; // Initialize $shop
        try {
            // First try to find by slug
            $shop = Shop::where('slug', $shopSlug)->with('company')->first();
            
            // If not found and shopSlug looks like an ID (numeric), try by ID
            if (!$shop && is_numeric($shopSlug)) {
                $shop = Shop::where('id', $shopSlug)->with('company')->first();
            }
            
            // If still not found, try by name (final fallback)
            if (!$shop) {
                // Convert underscores back to spaces for name lookup
                $shopName = str_replace('_', ' ', $shopSlug);
                $shop = Shop::where('name', $shopName)->with('company')->first();
            }
            
            if (!$shop) {
                abort(404);
            }

        } catch (\Exception $e) {
            abort(404);
        }

        // --- Adjust this query based on your actual data ---
        $items = Item::where("shop_id", $shop->id)
            // Option 1: Original - Assumes NULL means available
            ->whereNull("sold")
            ->whereNull("hold")

            // Option 2: Example - Assumes NULL OR 0 means available
            // ->where(function ($query) {
            //     $query->whereNull('sold')->orWhere('sold', 0);
            // })
            // ->where(function ($query) {
            //     $query->whereNull('hold')->orWhere('hold', 0);
            // })

            // Option 3: Example - Assumes NULL OR '' means available
            // ->where(function ($query) {
            //     $query->whereNull('sold')->orWhere('sold', '');
            // })
            // ->where(function ($query) {
            //     $query->whereNull('hold')->orWhere('hold', '');
            // })

            ->get();
        // --- End adjustment section ---


        // Log the number of items found for debugging

        // Keep dd() for immediate feedback during active debugging
        // Remove it once you confirm the count is correct
        // dd($items->pluck('id', 'name')); // Dump item names and IDs for easier checking


        return Inertia::render('PublicInventory/Index', [
            'items' => $items,
            'shopName' => $shop->name,
            'companyName' => $shop->company->name,
        ]);
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