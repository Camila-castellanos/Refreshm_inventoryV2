<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Storage;
use App\Models\DraftItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;

class UtilitiesController extends Controller
{
    /**
     * Display the find position page
     */
    public function findPositionPage()
    {
        $storages = Storage::all(['id', 'name', 'limit']);
        
        return Inertia::render('Utilities/FindPosition', [
            'storages' => $storages
        ]);
    }

    /**
     * Search for an item by storage and position
     */
    public function searchPosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'storage_id' => 'required|exists:storages,id',
            'position' => 'required|integer|min:1',
        ]);

        $storageId = $validated['storage_id'];
        $position = $validated['position'];

        $results = [];

        // Search in active inventory items (using same logic as Storage::getOccupiedPositions)
        $items = Item::where('storage_id', $storageId)
            ->where('position', $position)
            ->whereNull('sold')
            ->get();

        foreach ($items as $item) {
            $results[] = [
                'type' => 'inventory',
                'item' => $item,
                'draft' => null
            ];
        }

        // Search in draft items (using same logic as Storage::getOccupiedPositions)
        $draftItems = DraftItem::where('storage_id', $storageId)
            ->where('storage_position', $position)
            ->with(['draft:id,title,created_at'])
            ->get();

        foreach ($draftItems as $draftItem) {
            $draft = $draftItem->draft;
            $results[] = [
                'type' => 'draft',
                'item' => $draftItem,
                'draft' => $draft
            ];
        }

        if (count($results) > 0) {
            return response()->json([
                'found' => true,
                'results' => $results,
                'count' => count($results),
                'message' => count($results) === 1 
                    ? "1 item found at this position"
                    : count($results) . " items found at this position (duplicated location)"
            ], 200);
        }

        return response()->json([
            'found' => false,
            'results' => [],
            'count' => 0,
            'message' => "No item found at this position"
        ], 200);
    }
}
