<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $storages = Storage::with(['items', 'draftItems'])->get();
        return response()->json($storages);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        $validated = $request->validate([
            'storages' => 'required|array', // Debe ser un arreglo
            'storages.*.name' => 'required|string|max:255', // Cada objeto debe tener un campo "name"
            'storages.*.limit' => 'required|integer|min:1', // Cada objeto debe tener un campo "limit" y debe ser un número entero mayor a 0
        ]);

        foreach ($validated['storages'] as $storage) {
            Storage::create([
                'name' => $storage['name'],
                'limit' => $storage['limit'],
                'company_id' => Auth::user()->company_id,
            ]);
        }
    
        return response()->json(['message' => 'Storages created successfully.'], 201);
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
  public function store(Request $request)
{
    $validated = $request->validate([
        'storages' => 'required|array|min:1',
        'storages.*.name' => 'required|string|max:255',
        'storages.*.limit' => 'required|integer|min:1',
    ]);

    foreach ($validated['storages'] as $storage) {
        Storage::create([
            'name' => $storage['name'],
            'limit' => $storage['limit'],
            'company_id' => Auth::user()->company_id,
        ]);
    }

    return response()->json('Storages created successfully', 201);
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function edit(Request $request)
    // {
    //     // Validar que 'storages' sea un array y cada item tenga solo los campos permitidos
    //     $validatedData = $request->validate([
    //         'id' => 'required|exists:storages,id',
    //         'name' => 'sometimes|string|max:255',
    //         'limit' => 'sometimes|integer|min:1',
    //     ]);
    
    //     if(!$validatedData) {
    //         return response()->json([
    //             'message' => 'An unexpected error occurred.',
    //             'error' => $e->getMessage(),
    //         ], 400);
    //     }

    //     DB::transaction(function () use ($validatedData) {
    //         foreach ($validatedData['storages'] as $storageData) {
    //             $storage = Storage::find($storageData['id']);
    
    //             // Solo actualizar los campos permitidos
    //             $storage->update(array_intersect_key($storageData, array_flip([
    //                 'name', 'limit', 'location', 
    //             ])));
    //         }
    //     });
    
    //     return response()->json([
    //         'message' => 'Storages updated successfully!',
    //     ]);
    // }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'limit' => 'sometimes|integer|min:1',
        ]);
    
        // Buscar el Storage por ID
        $storage = Storage::findOrFail($id);
    
        // Actualizar solo los campos que se enviaron
        $storage->update(array_filter($validated));
    
        return response()->json(['message' => 'Storage updated successfully', 'storage' => $storage]);
    }
    

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        try {
     
            $validated = $request->validate([
                'storages' => ['required', 'array'],
                'storages.*' => ['integer', 'exists:storages,id'], 
            ]);
    
     
            Storage::destroy($validated['storages']);
    
            return response()->json([
                'message' => 'Storages deleted successfully.',
                'deleted_ids' => $validated['storages'],
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
       
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
         
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
}
