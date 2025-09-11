<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        
        $shop->save();

        return response()->json([
            'id' => $shop->id, 
            'name' => $shop->name,
            'slug' => $shop->slug
        ]);
    }
}
