<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MarketItemController extends Controller
{
    /**
     * Show the items list for photo management
     */
    public function index(Request $request, Market $market)
    {
        // Ensure the market belongs to the current user's company
        if ($market->shop->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this market.');
        }

        // Get search query
        $search = $request->input('search');

        // Get all items from the market's shop with photo counts
        $items = Item::where('shop_id', $market->shop_id)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('model', 'like', '%' . $search . '%')
                      ->orWhere('manufacturer', 'like', '%' . $search . '%')
                      ->orWhere('imei', 'like', '%' . $search . '%')
                      ->orWhere('type', 'like', '%' . $search . '%')
                      ->orWhere('colour', 'like', '%' . $search . '%');
                });
            })
            ->withCount('media')
            ->orderBy('model')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Ecommerce/MarketItemsEdit', [
            'market' => $market,
            'items' => $items,
            'filters' => [
                'search' => $search,
            ],
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

        // If request is AJAX, return JSON
        if (request()->wantsJson()) {
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
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        // Add photos to the item
        foreach ($request->file('photos') as $photo) {
            $item->addMedia($photo)
                ->toMediaCollection('item-photos');
        }

        // Reload item with updated photos
        $item->load('media');

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
