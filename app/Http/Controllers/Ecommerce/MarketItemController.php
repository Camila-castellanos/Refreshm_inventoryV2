<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MarketItemController extends Controller
{
    /**
     * Show the items list grouped by model
     */
    public function index(Request $request, Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $search = $request->input('search');

        // Get grouped models from market
        $models = $market->getGroupedModels($search, 20);

        return Inertia::render('Ecommerce/MarketItemsEdit', [
            'market' => $market,
            'models' => $models,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Get detailed variants for a specific model
     */
    public function modelDetails(Request $request, Market $market, $model)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $variants = $market->getModelVariants($model);

        if (!$variants) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        return response()->json($variants);
    }

    /**
     * Get all items for a specific model with market-specific pricing
     */
    public function byModel(Request $request, Market $market, Item $item)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        // Get all items with the same model - excluding sold items
        $items = Item::where('shop_id', $market->shop_id)
            ->where('model', $item->model)
            ->whereNull('sold')
            ->with('media')
            ->get();

        // Load custom prices and visibility for this market
        $marketData = DB::table('market_item_prices')
            ->where('market_id', $market->id)
            ->pluck('custom_price', 'item_id');
        
        $visibilityData = DB::table('market_item_prices')
            ->where('market_id', $market->id)
            ->pluck('is_visible', 'item_id');

        // Conditions that should be visible by default (if not configured)
        $visibleConditions = ['A', 'A-', 'B+', 'B'];

        $mappedItems = $items->map(function ($modelItem) use ($marketData, $visibilityData, $visibleConditions) {
            // Use custom price from market_item_prices, or fallback to selling_price
            $price = $marketData[$modelItem->id] ?? $modelItem->selling_price;
            
            // Determine visibility:
            // If already configured in market_item_prices, use that value
            // Otherwise, use default based on condition (A, A-, B+, B = visible, others = hidden)
            $isVisible = $visibilityData[$modelItem->id] ?? in_array($modelItem->grade, $visibleConditions);
            
            return [
                'id' => $modelItem->id,
                'model' => $modelItem->model,
                'manufacturer' => $modelItem->manufacturer,
                'imei' => $modelItem->imei,
                'type' => $modelItem->type,
                'colour' => $modelItem->colour,
                'condition' => $modelItem->grade,
                'status' => $modelItem->sold ? 'sold' : ($modelItem->hold ? 'hold' : 'available'),
                'buying_price' => $modelItem->buying_price,
                'selling_price' => $modelItem->selling_price,
                'market_price' => $price,
                'has_custom_price' => isset($marketData[$modelItem->id]),
                'is_visible' => $isVisible,
                'photo_count' => $modelItem->media->count(),
                'main_photo_thumb' => $modelItem->getFirstMediaUrl('item-photos', 'thumb'),
                'main_photo_url' => $modelItem->getFirstMediaUrl('item-photos'),
            ];
        });

        return response()->json([
            'market' => $market,
            'model' => [
                'id' => $item->id,
                'model' => $item->model,
                'manufacturer' => $item->manufacturer,
            ],
            'items' => $mappedItems,
        ]);
    }

    /**
     * Update the market price for an item
     */
    public function updatePrice(Request $request, Market $market, Item $item)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        // Update or create custom price for this market
        $market->setItemPrice($item->id, $request->price);

        return response()->json([
            'success' => true,
            'message' => 'Price updated successfully',
            'price' => $request->price,
        ]);
    }

    /**
     * Toggle item visibility in market
     */
    public function toggleVisibility(Request $request, Market $market, Item $item)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        $newVisibility = $market->toggleItemVisibility($item->id);

        return response()->json([
            'success' => true,
            'message' => $newVisibility ? 'Item is now visible' : 'Item is now hidden',
            'is_visible' => $newVisibility,
        ]);
    }

    /**
     * Show the photo management page for an item
     */
    public function edit(Market $market, Item $item)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        // Load item with photos
        $item->load('media');

        // If request is AJAX or explicitly asks for JSON, return JSON
        if (request()->wantsJson() || request()->query('format') === 'json') {
            return response()->json([
                'props' => [
                    'market' => $market,
                    'item' => $item,
                ]
            ]);
        }

        return Inertia::render('Ecommerce/MarketItemsEdit', [
            'market' => $market,
            'item' => $item,
        ]);
    }

    /**
     * Upload photos for an item
     */
    public function upload(Request $request, Market $market, Item $item)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        // Add photos to the item
        foreach ($request->file('photos') as $photo) {
            $item->addMedia($photo)
                ->toMediaCollection('item-photos');
        }

        // Reload item with updated photos
        $item->load('media');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($request->file('photos')) . ' photo(s) uploaded successfully!',
                'item' => $item
            ]);
        }

        return back()->with('success', count($request->file('photos')) . ' photo(s) uploaded successfully!');
    }

    /**
     * Delete a photo
     */
    public function delete(Market $market, Item $item, Media $media)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        // Ensure the media belongs to this item
        if ($media->model_id !== $item->id || $media->model_type !== Item::class) {
            abort(404, 'Photo not found for this item.');
        }

        // Delete the media
        $media->delete();

        // Reload item with updated photos
        $item->load('media');

        return back()->with('success', 'Photo deleted successfully!');
    }

    /**
     * Reorder photos
     */
    public function reorder(Request $request, Market $market, Item $item)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Ensure the item belongs to the market's shop
        if ($item->shop_id !== $market->shop_id) {
            abort(404, 'Item not found in this market.');
        }

        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:media,id',
        ]);

        // Reorder media
        $order = 1;
        foreach ($request->photo_ids as $photoId) {
            Media::where('id', $photoId)
                ->where('model_id', $item->id)
                ->where('model_type', Item::class)
                ->update(['order_column' => $order]);
            $order++;
        }

        // Reload item with updated photos
        $item->load('media');

        return back()->with('success', 'Photos reordered successfully!');
    }
}
