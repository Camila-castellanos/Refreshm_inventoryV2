<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        return response()->json(
          $req->user()->drafts()->latest()->get()
        );
    }

    /**
     * Display a listing of the resource in a simple way witouy payload.
     */
    public function simpleList(Request $req)
    {
        return Draft::where('user_id', $req->user()->id)
                ->orderByDesc('created_at')
                ->get(['id','payload->title as title','created_at']);
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
        $data = $req->validate([
          'id'      => 'sometimes|nullable|integer',
          'type'    => 'required|string',
          'payload' => 'required|array'
        ]);

        $userId = $req->user()->id;

    $draft = Draft::updateOrCreate(
        // search criteria
        [
            'id'      => $data['id'] ?? null,
            'user_id' => $userId
        ],
        // values to create or update
        [
            'type'    => $data['type'],
            'payload' => $data['payload']
        ]
    );

        return response()->json($draft, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Draft $draft)
    {
        return response()->json($draft);
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
            'type'    => 'sometimes|required|string',
            'payload' => 'sometimes|required|array'
        ]);

        if (isset($data['type'])) {
            $draft->type = $data['type'];
        }
        if (isset($data['payload'])) {
            $draft->payload = $data['payload'];
        }

        $draft->save();

        return response()->json($draft);
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
