<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Tab;
use Exception;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    /**
     * Return shop details as JSON.
     */
    public function show(Shop $shop)
    {
        // Basic ownership check placeholder - adjust later with policies
        // If you need stricter checks, replace with policies/guards
        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->company_id) && $shop->company_id !== $user->company_id) {
                return response()->json(['message' => 'Not authorized'], 403);
            }
        }

        return response()->json($shop);
    }

    /**
     * Update the shop name and slug.
     */
    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:shops,slug,' . $shop->id],
            'public_tabs' => ['sometimes', 'array'],
            'public_tabs.*' => ['integer'],
        ]);

        // Basic ownership check placeholder
        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->company_id) && $shop->company_id !== $user->company_id) {
                return response()->json(['message' => 'Not authorized'], 403);
            }
        }

        $shop->name = $data['name'];
        
        // Update slug if provided, otherwise let the model auto-generate it from the new name
        if (isset($data['slug'])) {
            $shop->slug = $data['slug'];
        }
        
        // Update public_tabs if provided
        if (isset($data['public_tabs'])) {
            $shop->public_tabs = $data['public_tabs'];
        }
        
        $shop->save();

        return response()->json([
            'id' => $shop->id, 
            'name' => $shop->name,
            'slug' => $shop->slug,
            'public_tabs' => $shop->public_tabs
        ]);
    }

    /**
     * Get all tabs for the authenticated user (shop owner).
     */
    public function getShopTabs(Shop $shop)
    {
        try {
            // Basic ownership check
            if (Auth::check()) {
                $user = Auth::user();
                if (isset($user->company_id) && $shop->company_id !== $user->company_id) {
                    return response()->json(['message' => 'Not authorized'], 403);
                }
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json(['tabs' => []], 200);
            }

            // Get all custom tabs for the user
            $tabs = Tab::where('user_id', $user->id)
                ->orderBy('order', 'asc')
                ->get();

            // Get the public_tabs IDs from the shop.
            $publicTabIds = $shop->public_tabs ?? [];

            // If no configuration exists, default to all user tabs being public
            if (empty($publicTabIds) && $tabs->count() > 0) {
                $publicTabIds = $tabs->pluck('id')->toArray();
            }

            // Add a 'is_public' flag to each tab
            $tabs = $tabs->map(function ($tab) use ($publicTabIds) {
                $tab->is_public = in_array($tab->id, $publicTabIds);
                return $tab;
            });

            return response()->json(['tabs' => $tabs], 200);
        } catch (Exception $e) {
            Log::error('ShopController@getShopTabs error: ' . $e->getMessage());
            return response()->json(['error' => 'Could not retrieve tabs'], 500);
        }
    }
}
