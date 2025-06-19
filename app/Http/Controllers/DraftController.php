<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $drafts = $req->user()->drafts()
                         ->latest()
                         ->with('items')
                         ->get();
        return response()->json($drafts);
    }

    /**
     * Display a listing of the resource in a simple way witouy payload.
     */
    public function simpleList(Request $req)
    {
        return Draft::where('user_id', $req->user()->id)
                     ->orderByDesc('created_at')
                     ->get(['id','title','created_at']);
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
    public function store(Request $req)
    {
        // Preserve full items array including all extra fields
        $allItems = $req->input('items', []);
        $data = $req->validate([
          'id'                 => 'sometimes|nullable|integer',
          'date'               => 'required|date',
          'title'              => 'required|string|max:255',
          'vendor'             => 'nullable|string|max:255',
          'items'              => 'required|array',
          'items.*.storage_id'         => 'required|integer|exists:storages,id',
          'items.*.storage_position'   => 'required|integer',
          // add other item fields validation as needed
        ]);

        $userId = $req->user()->id;

        $draft = Draft::updateOrCreate(
            ['id' => $data['id'] ?? null, 'user_id' => $userId],
            [
              'date'      => $data['date'],
              'vendor' =>    $data['vendor'] ?? null,
              'title'     => $data['title'],
            ]
        );
        // sync draft items
        $draft->items()->delete();
        Log::info('Data de la request: ', [$data]);
        foreach ($allItems as $item) {
            $item['tax_id'] = $item['tax'] ?? null;
            $draft->items()->create($item);
        }

        return response()->json($draft->load('items'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Draft $draft)
    {
        return response()->json($draft->load('items'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Draft $draft)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Draft $draft)
    {
        $data = $request->validate([
          'vendor_id'          => 'sometimes|required|integer|exists:vendors,id',
          'date'               => 'sometimes|required|date',
          'tax_id'             => 'sometimes|nullable|integer|exists:taxes,id',
          'title'              => 'sometimes|required|string|max:255',
          'vendor'             => 'nullable|string|max:255',
          'items'              => 'sometimes|required|array',
          'items.*.storage_id'         => 'required_with:items|integer|exists:storages,id',
          'items.*.storage_position'   => 'required_with:items|integer',
        ]);
        // preserve full items for update
        $allItems = $request->input('items', []);

        if (isset($data['vendor'])) {
            $draft->vendor_id = $data['vendor'];
        }
        if (isset($data['date'])) {
            $draft->date = $data['date'];
        }
        if (isset($data['title'])) {
            $draft->title = $data['title'];
        }
         
        $draft->save();
        // sync items if provided
        if (isset($data['items'])) {
            $draft->items()->delete();
            foreach ($allItems as $item) {
                $draft->items()->create($item);
            }
        }

        return response()->json($draft->load('items'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Draft $draft)
    {
        $draft->delete();

        return response()->json(null, 204);
    }
}
