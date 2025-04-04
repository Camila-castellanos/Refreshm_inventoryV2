<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Inertia\Inertia;
use App\Models\User;

class InventoryPublicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __invoke($usernameSlug): \Inertia\Response
    {

        $potentialUsername = str_replace('-', ' ', $usernameSlug);

        $user = User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($potentialUsername))])->first();

        // if ($user === null) {
        //    abort(404);
        // }

        $items = Item::where("user_id", $user->id)
        ->whereNull("sold")
        ->whereNull("hold")
        ->get();

        return Inertia::render('PublicInventory/Index', [
            'items' => $items, // Pass the collection under the key 'items'
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
