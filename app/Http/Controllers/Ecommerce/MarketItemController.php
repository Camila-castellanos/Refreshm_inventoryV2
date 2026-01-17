<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Ecommerce\MarketItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Get grouped models from market - includeHidden=true for admin view to show all models
        $models = $market->getGroupedModels($search, 20, null, null, 'latest', true);

        return Inertia::render('Ecommerce/MarketItemsEdit', [
            'market' => $market,
            'models' => $models,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Get detailed variants for a specific model (Admin view - shows all items including hidden)
     */
    public function modelDetails(Request $request, Market $market, $model)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Use unified method - includeHidden=true for admin view
        $result = $market->getModelsWithVariants($model, true);

        if (!$result) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        return response()->json($result);
    }

    /**
     * Get all items for a specific model with market-specific pricing (Admin view)
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

        // Use unified method - includeHidden=true for admin view
        $result = $market->getModelsWithVariants($item->model, true);

        if (!$result) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        return response()->json([
            'market' => $market,
            'model' => [
                'id' => $item->id,
                'model' => $result['model'],
                'manufacturer' => $result['manufacturer'],
            ],
            'items' => $result['items'],
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
     * Update the description for an item in a market
     */
    public function updateDescription(Request $request, Market $market, Item $item)
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
            'description' => 'nullable|string|max:1000',
        ]);

        $description = $this->sanitizeDescription($request->description);

        // Check if MarketItem already exists
        $existingMarketItem = MarketItem::where('market_id', $market->id)
            ->where('item_id', $item->id)
            ->first();

        // Update or create description for this market item
        // Preserve is_visible if exists, otherwise default to true
        $marketItem = MarketItem::updateOrCreate(
            [
                'market_id' => $market->id,
                'item_id' => $item->id,
            ],
            [
                'description' => $description,
                'is_visible' => $existingMarketItem ? $existingMarketItem->is_visible : true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Description updated successfully',
            'description' => $marketItem->description,
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

         // Get current visibility value from frontend (what frontend calculated)
         $currentValue = $request->input('current_value', null);
         
         // Toggle visibility - if frontend calculated true, we invert it
         $newVisibility = $market->toggleItemVisibility($item->id, $currentValue);

         return response()->json([
             'success' => true,
             'message' => $newVisibility ? 'Item is now visible' : 'Item is now hidden',
             'is_visible' => $newVisibility,
         ]);
     }

    /**
     * Set visibility for multiple items in a market
     */
    public function setBulkVisibility(Request $request, Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:items,id',
            'is_visible' => 'required|boolean',
        ]);

        $itemIds = $request->item_ids;
        $isVisible = $request->is_visible;

        // Update or create MarketItem entries for each item
        foreach ($itemIds as $itemId) {
            MarketItem::updateOrCreate(
                [
                    'market_id' => $market->id,
                    'item_id' => $itemId,
                ],
                [
                    'is_visible' => $isVisible,
                ]
            );
        }

        $action = $isVisible ? 'shown' : 'hidden';
        $count = count($itemIds);

        return response()->json([
            'success' => true,
            'message' => "{$count} item(s) are now {$action}",
            'is_visible' => $isVisible,
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
        // Include market-specific description/visibility
        $marketItem = MarketItem::where('market_id', $market->id)
            ->where('item_id', $item->id)
            ->first();

        $item->description = $marketItem?->description;
        $item->is_visible = $marketItem?->is_visible ?? true;

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

    /**
     * Sanitize rich text description allowing a limited set of tags/attributes
     */
    private function sanitizeDescription(?string $html): ?string
    {
        if (!$html) {
            return null;
        }

        // Remove script tags entirely
        $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

        // Allow only a safe set of tags
        $allowedTags = '<p><br><strong><em><u><s><ul><ol><li><a><img><h1><h2><h3><h4><blockquote><div><span>';
        $clean = strip_tags($clean, $allowedTags);

        // Strip inline event handlers and javascript: urls
        $clean = preg_replace('/on\w+="[^"]*"/i', '', $clean);
        $clean = preg_replace("/on\w+='[^']*'/i", '', $clean);
        $clean = preg_replace('/javascript:/i', '', $clean);

        // Remove style attributes to avoid injected CSS
        $clean = preg_replace('/\sstyle="[^"]*"/i', '', $clean);
        $clean = preg_replace("/\sstyle='[^']*'/i", '', $clean);

        return $clean;
    }
}
