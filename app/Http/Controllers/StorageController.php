<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Return storages ordered by priority (lower number = higher priority)
        // Additionally compute the real occupied positions count per storage
        // by combining Item positions (only items not sold) and DraftItem positions.
        $storages = Storage::with(['items', 'draftItems'])->orderBy('priority', 'asc')->get();

        // If no storages, just return early
        if ($storages->isEmpty()) {
            return response()->json($storages);
        }

        $storageIds = $storages->pluck('id')->toArray();

        // Get positions from Items: only consider items with a numeric position and not sold
        $itemPositions = \App\Models\Item::whereIn('storage_id', $storageIds)
            ->whereNotNull('position')
            ->whereNull('sold')
            ->get(['storage_id', 'position']);

        // Group positions by storage_id and make them unique
        $itemPositionsByStorage = $itemPositions->groupBy('storage_id')
            ->map(function ($group) {
                return $group->pluck('position')->unique()->values()->toArray();
            })->toArray();

        // Get positions from DraftItems (they use storage_position)
        $draftPositions = \App\Models\DraftItem::whereIn('storage_id', $storageIds)
            ->whereNotNull('storage_position')
            ->get(['storage_id', 'storage_position']);

        $draftPositionsByStorage = $draftPositions->groupBy('storage_id')
            ->map(function ($group) {
                return $group->pluck('storage_position')->unique()->values()->toArray();
            })->toArray();

        // Attach computed fields to each storage
        foreach ($storages as $s) {
            $id = $s->id;

            $positionsFromItems = $itemPositionsByStorage[$id] ?? [];
            $positionsFromDrafts = $draftPositionsByStorage[$id] ?? [];

            $occupied = array_unique(array_merge($positionsFromItems, $positionsFromDrafts));

            $s->occupied_count = count($occupied);
            // available slots = limit - occupied (never negative)
            $limit = (int) ($s->limit ?? 0);
            $s->available_slots = max(0, $limit - $s->occupied_count);
        }

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

    /**
     * Reorder storages by provided order array.
     * Expects: { order: [id1, id2, ...] }
     */
    public function reorder(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:storages,id',
        ]);

        $companyId = Auth::user()->company_id ?? null;

        try {
            DB::transaction(function () use ($validated, $companyId) {
                foreach ($validated['order'] as $idx => $id) {
                    $storage = Storage::where('id', $id)
                        ->when($companyId, function ($q) use ($companyId) { return $q->where('company_id', $companyId); })
                        ->first();

                    if ($storage) {
                        $storage->priority = $idx + 1;
                        $storage->save();
                    }
                }
            });

            return response()->json(['message' => 'Order updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Find the first available position across all storages (ordered by priority).
     * Returns: { storage_id, position } or null if no space available
     *
     * @param array $occupiedPositions Array of ['storage_id' => X, 'position' => Y] already occupied
     * @return array|null
     */
    public static function findFirstAvailablePosition($occupiedPositions = [])
    {
        // Get all storages ordered by priority (ascending = higher priority first)
        $storages = Storage::orderBy('priority', 'asc')->get();

        if ($storages->isEmpty()) {
            return null;
        }

        // Build a map of occupied positions by storage
        $occupiedByStorage = [];
        foreach ($occupiedPositions as $occupied) {
            $storageId = $occupied['storage_id'] ?? null;
            $position = $occupied['position'] ?? null;
            if ($storageId && $position) {
                if (!isset($occupiedByStorage[$storageId])) {
                    $occupiedByStorage[$storageId] = [];
                }
                $occupiedByStorage[$storageId][] = $position;
            }
        }

        // Try each storage in priority order
        foreach ($storages as $storage) {
            // Get occupied positions from DB items (not sold)
            $dbPositions = \App\Models\Item::where('storage_id', $storage->id)
                ->whereNull('sold')
                ->whereNotNull('position')
                ->pluck('position')
                ->toArray();

            // Get occupied positions from draft items
            $draftPositions = \App\Models\DraftItem::where('storage_id', $storage->id)
                ->whereNotNull('storage_position')
                ->pluck('storage_position')
                ->toArray();

            // Get occupied positions from the provided array
            $providedOccupied = $occupiedByStorage[$storage->id] ?? [];

            // Merge all occupied positions
            $occupied = array_unique(array_merge($dbPositions, $draftPositions, $providedOccupied));

            // Find first available position in this storage
            for ($i = 1; $i <= (int)$storage->limit; $i++) {
                if (!in_array($i, $occupied)) {
                    return [
                        'storage_id' => $storage->id,
                        'position' => $i,
                    ];
                }
            }
        }

        // No available position found
        return null;
    }

    /**
     * Assign storage positions to items based on priority and available space.
     * Expects: { 
     *   items: [ { id?, manufacturer?, model?, ... } ],  // items sin posición asignada
     *   assigned: [ { storage_id, position, ... } ]      // items ya asignados en el front (reservados)
     * }
     * Returns: { items: [ { storage_id, position, ... } ], unassigned_count: number }
     */
    public function assignPositions(Request $request)
    {
        $items = $request->input('items', []);
        $assignedInFront = $request->input('assigned', []);
        
        if (!is_array($items) || empty($items)) {
            return response()->json([
                'message' => 'Invalid request: items must be a non-empty array',
                'items' => [],
                'unassigned_count' => 0,
            ], 400);
        }

        $assigned = [];
        $unassigned = [];

        // Get all storages ordered by priority (ascending = higher priority first)
        $storages = Storage::orderBy('priority', 'asc')->get();

        if ($storages->isEmpty()) {
            return response()->json([
                'message' => 'No storages available',
                'items' => [],
                'unassigned_count' => count($items),
            ], 400);
        }

        // Process each item
        foreach ($items as $item) {
            $itemAssigned = false;

            // Try to assign this item to the first available storage (by priority)
            foreach ($storages as $storage) {
                // Get occupied positions from DB items
                // Ignore items that have already been sold (sold != null)
                $dbPositions = \App\Models\Item::where('storage_id', $storage->id)
                    ->whereNull('sold')
                    ->whereNotNull('position')
                    ->pluck('position')
                    ->toArray();

                // Get occupied positions from draft items
                $draftPositions = \App\Models\DraftItem::where('storage_id', $storage->id)
                    ->whereNotNull('storage_position')
                    ->pluck('storage_position')
                    ->toArray();

                // Get occupied positions from items already assigned in the front (reserved)
                $frontAssignedPositions = collect($assignedInFront)
                    ->filter(fn($assignedItem) => $assignedItem['storage_id'] === $storage->id)
                    ->pluck('position')
                    ->toArray();

                // Get occupied positions from items already assigned in this batch
                $batchPositions = collect($assigned)
                    ->filter(fn($assignedItem) => $assignedItem['storage_id'] === $storage->id)
                    ->pluck('position')
                    ->toArray();

                $occupied = array_unique(array_merge($dbPositions, $draftPositions, $frontAssignedPositions, $batchPositions));
                Log::info("Storage {$storage->id} occupied positions: " . implode(',', $occupied));
                // Find first available position in this storage
                for ($i = 1; $i <= (int)$storage->limit; $i++) {
                    if (!in_array($i, $occupied)) {
                        // Assign this item to this position
                        $item['storage_id'] = $storage->id;
                        $item['position'] = $i;
                        $assigned[] = $item;
                        $itemAssigned = true;
                        break;
                    }
                }

                // If item was assigned, move to next item
                if ($itemAssigned) {
                    break;
                }
            }

            // If item couldn't be assigned to any storage, add to unassigned
            if (!$itemAssigned) {
                $unassigned[] = $item;
            }
        }

        return response()->json([
            'message' => count($unassigned) > 0 
                ? 'Some items could not be assigned due to insufficient storage capacity'
                : 'All items assigned successfully',
            'items' => $assigned,
            'unassigned_count' => count($unassigned),
        ], count($unassigned) > 0 ? 207 : 200);
    }
}
