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
     * Update the shop name.
     */
    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Basic ownership check placeholder
        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->company_id) && $shop->company_id !== $user->company_id) {
                return response()->json(['message' => 'Not authorized'], 403);
            }
        }

        $shop->name = $data['name'];
        $shop->save();

        return response()->json(['id' => $shop->id, 'name' => $shop->name]);
    }
}
