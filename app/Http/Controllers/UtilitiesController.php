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

        // Search in active inventory items (using same logic as Storage::getOccupiedPositions)
        $item = Item::where('storage_id', $storageId)
            ->where('position', $position)
            ->whereNull('sold')
            ->first();

        if ($item) {
            return response()->json([
                'found' => true,
                'type' => 'inventory',
                'item' => $item,
                'draft' => null,
                'message' => "Item found in active inventory"
            ], 200);
        }

        // Search in draft items (using same logic as Storage::getOccupiedPositions)
        $draftItem = DraftItem::where('storage_id', $storageId)
            ->where('storage_position', $position)
            ->with(['draft:id,title,created_at'])
            ->first();

        if ($draftItem) {
            $draft = $draftItem->draft;
            $draftLabel = $draft ? "{$draft->title} ({$draft->created_at->format('Y-m-d')})" : 'Unknown Draft';
            
            return response()->json([
                'found' => true,
                'type' => 'draft',
                'item' => $draftItem,
                'draft' => $draft,
                'message' => "Item found in draft: {$draftLabel}"
            ], 200);
        }

        return response()->json([
            'found' => false,
            'type' => null,
            'item' => null,
            'draft' => null,
            'message' => "No item found at this position"
        ], 200);
    }
}
