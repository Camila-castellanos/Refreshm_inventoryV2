<?php

namespace App\Http\Controllers;

use App\Exports\ItemsExample;
use App\Http\Requests\ItemExcelForm;
use App\Http\Requests\ItemForm;
use App\Http\Requests\ItemsWithBillForm;
use App\Http\Requests\RequestItemsForm;
use App\Imports\ItemsImport;
use App\Mail\RequestItems;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Item;
use App\Models\Manufacturer;
use App\Models\Models;
use App\Models\ReturnItems;
use App\Models\Sale;
use App\Models\Storage;
use App\Models\Tab;
use App\Models\TabHistory;
use App\Models\TabItem;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        $user = Auth::user();

        $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
        $customFields = CustomField::where('user_id', $user->id)->get();

        // Con el nuevo scope global, ya no necesitamos filtrar por user_id
        $context = [
            'items' => Item::with(['storage' => function ($query) {
                $query->select('id', 'name', 'limit') // other columns
                    ->withCount('items'); // current count of items in storage
            }, 'vendor:id,vendor'])
                ->whereIn('type', ['device', 'accessory'])
                ->whereNull('sold')
                ->whereNull('hold')
                ->whereNotIn('id', TabItem::pluck('item_id'))
                ->get(),
            'tabs' => $tabs,
            'fields' => $customFields,
        ];

        $context['customers'] = Customer::all();

        return Inertia::render('Inventory/Index', $context);
    }

    public function getItems(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        // Con el nuevo scope global, ya no necesitamos filtrar por user_id
        $items = Item::with(['storage' => function ($query) {
            $query->select('id', 'name', 'limit') // other columns
                ->withCount('items'); // current count of items in storage
        }, 'vendor:id,vendor'])
            ->whereIn('type', ['device', 'accessory'])
            ->whereNull('sold')
            ->whereNull('hold')
            ->whereNotIn('id', TabItem::pluck('item_id'))
            ->get();

        return response()->json($items);
    }

    /**
     * Get specific items by their IDs to check their current status (e.g., if they're sold)
     * Expects payload: { ids: [1, 2, 3, ...] }
     * Returns items with all their data including sold status
     */
    public function getSpecificItems(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $data['ids'];

        if (empty($ids)) {
            return response()->json([]);
        }

        $items = Item::with(['storage' => function ($query) {
            $query->select('id', 'name', 'limit')
                ->withCount('items');
        }, 'vendor:id,vendor'])
            ->whereIn('id', $ids)
            ->get();

        return response()->json($items);
    }

    /**
     * Return unique normalized models for given manufacturers.
     * Expects payload: { manufacturers: ["Apple", "Samsung"] }
     * Normalizes manufacturer matching (case-insensitive) and model normalization
     * (removes storage sizes like "128GB" so "iPhone 15 128GB" and "iPhone 15 256GB" collapse to "iPhone 15").
     */
    public function getUniqueModelsByManufacturer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manufacturers' => 'required|array',
            'manufacturers.*' => 'string',
        ]);

        $manufacturers = array_map(fn ($m) => mb_strtolower(trim($m)), $data['manufacturers']);

        if (empty($manufacturers)) {
            return response()->json(['models' => []]);
        }

        // Query items active in inventory (not sold, not on hold) and matching manufacturers case-insensitively
        $models = Item::whereNull('sold')
            ->whereNull('hold')
            ->whereIn('type', ['device'])
            ->whereIn(DB::raw('LOWER(manufacturer)'), $manufacturers)
            ->pluck('model')
            ->filter()
            ->values();

        // Normalize models: remove storage sizes like '128GB', parentheses content, punctuation and collapse whitespace
        $normalized = collect($models)->map(function ($model) {
            $m = trim((string) $model);
            // Remove parenthetical notes: "(Unlocked)", etc.
            $m = preg_replace('/\(.+?\)/u', ' ', $m);
            // Remove storage sizes like '128GB', '256 GB', case-insensitive
            $m = preg_replace('/\b\d+\s*gb\b/iu', ' ', $m);
            // Remove other common capacity notations like '128gb', '128 g', '128g'
            $m = preg_replace('/\b\d+\s*g\b/iu', ' ', $m);
            // Replace any non-alphanumeric (except spaces) with space
            $m = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $m);
            // Collapse multiple spaces into one
            $m = preg_replace('/\s+/u', ' ', $m);
            $m = trim($m);

            return mb_strtolower($m);
        })->filter()->unique()->values()->sort()->map(function ($m) {
            return ['label' => mb_convert_case($m, MB_CASE_TITLE, 'UTF-8'), 'value' => $m];
        })->values()->all();

        return response()->json(['models' => $normalized]);
    }

    public function assignStorage(Request $request)
    {
        $request->validate([
            'storage_id' => 'required|exists:storages,id',
            'items' => 'required|array',
            'items.*' => 'exists:items,id',
        ]);

        $storageId = $request->storage_id;
        $items = $request->items;

        $storage = Storage::findOrFail($storageId);

        $currentItemCount = Item::where('storage_id', $storageId)->count();

        if ($currentItemCount + count($items) > $storage->limit) {
            return response()->json([
                'message' => 'Not enough space in the selected storage.',
                'current_count' => $currentItemCount,
                'items_received' => count($items),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $itemsToUpdate = Item::whereIn('id', $items)
                ->where(function ($query) use ($storageId) {
                    $query->whereNull('storage_id')
                        ->orWhere('storage_id', '!=', $storageId);
                })
                ->get();

            if ($itemsToUpdate->isEmpty()) {
                return response()->json([
                    'message' => 'All selected items are already assigned to this storage.',
                ], 400);
            }

            $updatedItems = [];
            foreach ($itemsToUpdate as $item) {

                $item->update([
                    'position' => null,
                    'storage_id' => null,
                ]);

                $newPosition = Item::getNextAvailablePosition($storageId);
                $item->update([
                    'storage_id' => $storageId,
                    'position' => $newPosition,
                ]);

                $updatedItems[] = $item->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Items assigned successfully!',
                'storage' => $storage,
                'updated_items' => $updatedItems,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error while assigning items.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function public(): \Inertia\Response
    {
        $role = 'GUEST';

        if (Auth::user() != null) {
            $role = Auth::user()->role;
        }

        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabs = Tab::where('user_id', $user)->orderBy('order', 'asc')->get();

        $items = Item::where('user_id', $user)
            ->whereNull('sold')
            ->whereIn('type', ['device', 'accessory'])
            ->whereNull('hold')
            ->get();

        $items = Item::where('user_id', $user)->whereNull('sold')->whereNull('hold')->get();
        $count = 0;
        $manufacturers = [];

        foreach ($items as $item) {
            $manufacturerName = trim($item->manufacturer);
            if (! empty($manufacturerName)) {
                $count++;
                $manufacturerExists = false;
                foreach ($manufacturers as $existingManufacturer) {
                    if ($existingManufacturer['name'] === $manufacturerName) {
                        $manufacturerExists = true;
                        break;
                    }
                }
                // If the manufacturer doesn't exist, add it to the $manufacturers array
                if (! $manufacturerExists) {
                    $count++; // Increments the count
                    $manufacturers[] = [
                        'id' => $count, // Assigns the value of the variable $count to the key "id"
                        'name' => $manufacturerName, // Assigns the value of the variable $manufacturerName to the key "name"
                    ];
                }
            }
            if ($item->model && strpos($item->model, 'Housing') !== false) {
                $item->tab = 'Housing';
            } elseif ($item->model && strpos($item->model, 'Digitizer') !== false) {
                $item->tab = 'Digitizer';
            } else {
                $tabItem = TabItem::where('item_id', $item->id)->get();
                if (! $tabItem->isEmpty()) {
                    $item->tab = $tabItem[0]->getTab($tabItem[0]->tab_id)->name;
                }
            }
        }

        $context = [
            'items' => $items,
            'tabs' => $tabs,
            'role' => $role,
            'manufacturers' => $manufacturers,
        ];

        return Inertia::render('Items/Public', $context);
    }

    public function request(RequestItemsForm $request)
    {
        $data = $request->validated();
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $store = $data['store'] ?? null;
        $notes = $data['notes'] ?? null;
        $itemsInput = $data['items'] ?? [];

        // Normalize items - merge with full item data from database
        $itemsNormalized = collect($itemsInput)->map(function ($it) {
            // Convert to array if needed
            if (is_object($it)) {
                $it = (array) $it;
            }

            // If item has an id, fetch the full item data from DB
            if (isset($it['id']) && is_numeric($it['id'])) {
                $dbItem = Item::find((int) $it['id']);
                if ($dbItem) {
                    $fullItem = $dbItem->toArray();
                    // Override with the frontend data (selling_price and currency)
                    $fullItem['selling_price'] = $it['selling_price'] ?? $fullItem['selling_price'];
                    $fullItem['currency'] = $it['currency'] ?? 'CAD';

                    return $fullItem;
                }
            }

            return is_array($it) ? $it : null;
        })->filter()->values();

        $uniqueItems = $itemsNormalized->unique('id')->values()->all();

        // Determine request owner: use the first non-null item's user_id if present, otherwise current user
        $requestUserId = Auth::id();
        foreach ($uniqueItems as $itTemp) {
            if (isset($itTemp['user_id']) && $itTemp['user_id'] !== null) {
                $requestUserId = $itTemp['user_id'];
                break;
            }
        }

        // Persist incoming request
        $shippingData = $data['shipping'] ?? null;
        $incoming = IncomingRequest::create([
            'name' => $name,
            'email' => $email,
            'store' => $store,
            'notes' => $notes,
            'user_id' => $requestUserId,
            'shipping' => $shippingData,
        ]);

        // Persist each requested item as a snapshot
        foreach ($uniqueItems as $it) {
            IncomingRequestItem::create([
                'incoming_request_id' => $incoming->id,
                'original_item_id' => $it['id'] ?? null,
                'date' => $it['date'] ?? null,
                'supplier' => $it['supplier'] ?? null,
                'manufacturer' => $it['manufacturer'] ?? null,
                'storage_id' => $it['storage_id'] ?? null,
                'position' => $it['position'] ?? null,
                'model' => $it['model'] ?? null,
                'colour' => $it['colour'] ?? null,
                'battery' => $it['battery'] ?? null,
                'grade' => $it['grade'] ?? null,
                'issues' => $it['issues'] ?? null,
                'cost' => $it['cost'] ?? null,
                'imei' => $it['imei'] ?? null,
                'selling_price' => $it['selling_price'] ?? null,
                'currency' => $it['currency'] ?? 'CAD',
                'customer' => $it['customer'] ?? null,
                'user_id' => $it['user_id'] ?? Auth::id(),
                'vendor_id' => $it['vendor_id'] ?? null,
                'shop_id' => $it['shop_id'] ?? null,
                'type' => $it['type'] ?? null,
            ]);
        }

        // Determine email destination from items' users or use default
        $destinationEmail = 'will@refreshmobile.ca'; // Default email

        // Try to get email from the first item's user
        foreach ($uniqueItems as $item) {
            if (isset($item['user_id']) && $item['user_id']) {
                $user = User::find($item['user_id']);
                if ($user && $user->email) {
                    $destinationEmail = $user->email;
                    break; // Use the first valid email found
                }
            }
        }

        // Send email with proper error handling
        try {
            Mail::to($destinationEmail)->send(new RequestItems($name, $email, $store, $notes, $uniqueItems));

            Log::info('RequestItems email sent successfully', [
                'incoming_request_id' => $incoming->id,
                'destination_email' => $destinationEmail,
                'items_count' => count($uniqueItems),
            ]);

            return response()->json([
                'saved' => true,
                'id' => $incoming->id,
                'email_sent' => true,
                'message' => 'Request saved and email sent successfully',
                'destination_email' => $destinationEmail,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send RequestItems email', [
                'error' => $e->getMessage(),
                'incoming_request_id' => $incoming->id,
                'destination_email' => $destinationEmail,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'saved' => true,
                'id' => $incoming->id,
                'email_sent' => false,
                'message' => 'Request saved but failed to send email: '.$e->getMessage(),
                'destination_email' => $destinationEmail,
            ], 500);
        }

    }

    /**
     * Return all incoming requests where processed = false
     */
    public function incomingRequests(Request $request): \Illuminate\Http\JsonResponse
    {
        $processedParam = $request->query('processed', false);

        $requests = IncomingRequest::with(['items.originalItem'])
            ->where('processed', $processedParam)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fallback logic: if specific fields are null in IncomingRequestItem,
        // try to pull them from the related originalItem.
        $requests->each(function ($req) {
            $req->items->each(function ($item) {
                if ($item->originalItem) {
                    $item->supplier = $item->supplier ?? $item->originalItem->supplier;
                    $item->manufacturer = $item->manufacturer ?? $item->originalItem->manufacturer;
                    $item->storage_id = $item->storage_id ?? $item->originalItem->storage_id;
                    $item->position = $item->position ?? $item->originalItem->position;
                    $item->model = $item->model ?? $item->originalItem->model;
                    $item->colour = $item->colour ?? $item->originalItem->colour;
                    $item->battery = $item->battery ?? $item->originalItem->battery;
                    $item->grade = $item->grade ?? $item->originalItem->grade;
                    $item->issues = $item->issues ?? $item->originalItem->issues;
                    $item->cost = $item->cost ?? $item->originalItem->cost;
                    $item->imei = $item->imei ?? $item->originalItem->imei;
                    $item->type = $item->type ?? $item->originalItem->type;
                }
            });
        });

        return response()->json($requests);
    }

    /**
     * Delete a single item from an incoming request
     */
    public function deleteIncomingRequestItem($id)
    {
        $item = IncomingRequestItem::findOrFail($id);
        $item->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Create a simple invoice (Sale) from an incoming request
     */
    public function createInvoiceFromRequest($id)
    {
        $requestModel = IncomingRequest::with('items')->findOrFail($id);

        $userId = $requestModel->user_id ?? auth()->id();

        $subtotal = 0;
        $items = $requestModel->items ?? [];
        foreach ($items as $it) {
            $subtotal += (float) ($it->selling_price ?? $it->cost ?? 0);
        }

        $sale = Sale::create([
            'user_id' => $userId,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'date' => now(),
        ]);

        $receiptUrl = route('sales.receipt', $sale);

        return response()->json(['created' => true, 'receipt' => $receiptUrl, 'sale_id' => $sale->id], 201);
    }

    /**
     * Delete an entire incoming request with its items
     */
    public function deleteIncomingRequest($id)
    {
        $req = IncomingRequest::findOrFail($id);
        $req->delete();

        return response()->json(['deleted' => true]);
    }

    public function lcd()
    {
        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabs = Tab::where('user_id', $user)->orderBy('order', 'asc')->get();
        $searchTerm = 'Digitizer';

        $context = [
            'items' => Item::where('user_id', $user)->whereNull('sold')->whereNull('hold')->where('user_id', $user)->where('model', 'LIKE', '%'.$searchTerm.'%')->get(),
        ];

        return response()->json($context);
    }

    public function housing()
    {
        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabs = Tab::where('user_id', $user)->orderBy('order', 'asc')->get();
        $searchTerm = 'Housing';

        $context = [
            'items' => Item::where('user_id', $user)->whereNull('sold')->whereNull('hold')->where('model', 'LIKE', '%'.$searchTerm.'%')->get(),
        ];

        return response()->json($context);
    }

    public function customTab($id)
    {
        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabItems = TabItem::where('tab_id', $id)->pluck('item_id');
        $context = [
            'items' => Item::whereNull('sold')->whereNull('hold')->whereIn('id', $tabItems)->get(),
        ];

        return response()->json($context);
    }

    /**
     * Return a list of resources
     *
     * @return \Illuminate\Http\Response
     */
    public function list(): \Illuminate\Http\JsonResponse
    {
        // Con el nuevo scope global, ya no necesitamos filtrar por user_id
        return response()->json(Item::whereNull('sold')->whereNull('hold')->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): \Inertia\Response
    {
        $user = Auth::user();
        $customFields = CustomField::where('user_id', $user->id)->get();

        return Inertia::render('Items/CreateEdit', ['fields' => $customFields]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItemForm $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        $items = $request->input('items');
        $draftId = $request->input('draft_id');

        // Convert items to arrays if they are objects (e.g., DraftItem models)
        $items = collect($items)->map(function ($item) {
            if (is_object($item)) {
                return is_a($item, 'Illuminate\Database\Eloquent\Model')
                    ? $item->toArray()
                    : (array) $item;
            }

            return $item;
        })->toArray();

        $user = $request->user();
        $company = $user->company;
        $shops = $company?->shops;
        $firstShop = $shops[0] ?? null;
        $shopId = $firstShop ? $firstShop->id : null;

        // Build occupied positions map and check for conflicts
        $result = $this->buildOccupiedMapAndCheckConflicts($items, $draftId);

        if ($result['has_conflicts']) {
            return response()->json([
                'message' => 'Storage position conflicts detected',
                'conflicts' => $result['conflicts'],
            ], 422);
        }

        if ($result['error']) {
            return response()->json(['message' => $result['error']], 422);
        }

        // Use the processed items with updated positions
        $items = $result['items'];

        $created = [];

        try {
            DB::beginTransaction();

            foreach ($items as $item) {
                $item['user_id'] = Auth::user()->id;
                $item['shop_id'] = $shopId;

                // Set default type if null
                if (empty($item['type'])) {
                    $item['type'] = 'device';
                }

                // Append current time to date if only Y-m-d format
                if (! empty($item['date']) && strlen($item['date']) === 10) {
                    $item['date'] = Carbon::createFromFormat('Y-m-d', $item['date'])
                        ->setTimeFromTimeString(Carbon::now()->toTimeString())
                        ->format('Y-m-d H:i:s');
                }

                $created[] = Item::create($item);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating items', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error creating items',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json($created, 201);
    }

    /**
     * Build a map of occupied positions and check for conflicts.
     *
     * Logic:
     * 1. Get all occupied positions from active items in inventory
     * 2. Get occupied positions from other drafts (excluding current draft_id)
     * 3. For each incoming item, check if its position conflicts
     * 4. If conflict, suggest a new available position
     *
     * @param  array  $items  Items to check
     * @param  int|null  $draftId  Current draft ID to exclude from conflict check
     * @return array ['items' => processed items, 'has_conflicts' => bool, 'conflicts' => array, 'error' => string|null]
     */
    private function buildOccupiedMapAndCheckConflicts(array $items, ?int $draftId = null): array
    {
        $storageIds = collect($items)->pluck('storage_id')->filter()->unique()->toArray();
        $conflicts = [];

        if (! empty($storageIds)) {
            // Clean up sold items that still have active positions
            Item::whereIn('storage_id', $storageIds)
                ->whereNotNull('position')
                ->whereNotNull('sold')
                ->update([
                    'sold_storage_id' => DB::raw('storage_id'),
                    'sold_position' => DB::raw('position'),
                    'storage_id' => null,
                    'position' => null,
                ]);
        }

        // Use unified method to get all occupied positions
        $occupiedByStorage = Storage::getOccupiedPositionsBatch($storageIds, $draftId);

        // Convert to flip format for O(1) lookup
        $occupiedFlipped = [];
        foreach ($occupiedByStorage as $sid => $positions) {
            $occupiedFlipped[$sid] = array_flip($positions);
        }

        // Get storage limits
        $storageLimits = Storage::whereIn('id', $storageIds)->pluck('limit', 'id')->toArray();

        // Track positions we're assigning in this batch (to avoid duplicates within the batch)
        $batchAssigned = [];

        // Process items and check for conflicts
        foreach ($items as $idx => &$item) {
            if (empty($item['storage_id'])) {
                continue;
            }

            $sid = $item['storage_id'];
            $pos = $item['position'] ?? null;
            $limit = $storageLimits[$sid] ?? 999999;

            // Initialize tracking for this storage if needed
            if (! isset($occupiedFlipped[$sid])) {
                $occupiedFlipped[$sid] = [];
            }
            if (! isset($batchAssigned[$sid])) {
                $batchAssigned[$sid] = [];
            }

            $hasConflict = false;

            // Check if position is occupied (from DB or from batch)
            if ($pos && (isset($occupiedFlipped[$sid][$pos]) || isset($batchAssigned[$sid][$pos]))) {
                $hasConflict = true;
            }

            if ($hasConflict || ! $pos) {
                // Find next available position
                $found = false;
                $newSid = $sid;
                $newPos = null;

                // 1. Try same storage first
                for ($p = 1; $p <= $limit; $p++) {
                    if (! isset($occupiedFlipped[$sid][$p]) && ! isset($batchAssigned[$sid][$p])) {
                        $newPos = $p;
                        $found = true;
                        break;
                    }
                }

                // 2. If storage is full, try other storages using unified method
                if (! $found) {
                    // Build additional occupied from batch assignments
                    $additionalOccupied = [];
                    foreach ($batchAssigned as $sId => $positions) {
                        foreach ($positions as $pPos => $val) {
                            $additionalOccupied[] = ['storage_id' => $sId, 'position' => $pPos];
                        }
                    }

                    $suggestion = Storage::findFirstAvailablePosition($draftId, $additionalOccupied);

                    if ($suggestion) {
                        $newSid = $suggestion['storage_id'];
                        $newPos = $suggestion['position'];
                        $found = true;

                        // Initialize tracking for the new storage if needed
                        if (! isset($occupiedFlipped[$newSid])) {
                            $occupiedFlipped[$newSid] = [];
                        }
                        if (! isset($batchAssigned[$newSid])) {
                            $batchAssigned[$newSid] = [];
                        }
                    }
                }

                if ($found) {
                    // Mark the new position as assigned in batch
                    $batchAssigned[$newSid][$newPos] = true;

                    // Get storage name for message
                    $suggestedStorageName = Storage::find($newSid)->name ?? "Storage {$newSid}";
                    $originalStorageName = Storage::find($sid)->name ?? "Storage {$sid}";

                    $msg = $hasConflict
                        ? "Position {$pos} in {$originalStorageName} is occupied. Suggested: {$suggestedStorageName} position {$newPos}"
                        : "No position assigned. Suggested: {$suggestedStorageName} position {$newPos}";

                    $conflicts[] = [
                        'item_index' => $idx,
                        'item_model' => $item['model'] ?? 'Unknown',
                        'item_imei' => $item['imei'] ?? null,
                        'original_storage_id' => $sid,
                        'original_position' => $pos,
                        'suggested_storage_id' => $newSid,
                        'suggested_position' => $newPos,
                        'message' => $msg,
                    ];
                } else {
                    $itemModel = $item['model'] ?? 'Unknown';

                    return [
                        'items' => $items,
                        'has_conflicts' => false,
                        'conflicts' => [],
                        'error' => "All storages are full. Cannot find available position for item #{$idx} ({$itemModel}).",
                    ];
                }
            } else {
                // No conflict, mark position as assigned in batch
                $batchAssigned[$sid][$pos] = true;
            }
        }
        unset($item);

        return [
            'items' => $items,
            'has_conflicts' => ! empty($conflicts),
            'conflicts' => $conflicts,
            'error' => null,
        ];
    }

    public function storeWithBill(ItemsWithBillForm $request)
    {
        // Validate and extract items data and bill data
        $validated = $request->validated();
        $billData = $request->input('bill');
        $itemsData = $request->input('items');

        // Get draft_id if provided (for excluding from conflict check)
        $draftId = $request->has('draft_id') ? $request->draft_id : null;

        // Use the reusable method for conflict detection
        $conflictResult = $this->buildOccupiedMapAndCheckConflicts($itemsData, $draftId);

        // Handle error (all storages full)
        if ($conflictResult['error']) {
            return response()->json([
                'message' => $conflictResult['error'],
            ], 422);
        }

        // Return conflicts to frontend if any
        if ($conflictResult['has_conflicts']) {
            return response()->json([
                'message' => 'Storage position conflicts detected',
                'conflicts' => $conflictResult['conflicts'],
            ], 422);
        }

        // Use the updated items data from conflict check
        $itemsData = $conflictResult['items'];

        // sum subtotals of items
        $sumSubtotals = collect($itemsData)->sum(fn ($i) => $i['subtotal'] ?? 0);

        // sum of totals of items
        $sumTotals = collect($itemsData)->sum(function ($i) use ($billData) {
            if (isset($i['cost'])) {
                return $i['cost'];
            }
            // if cost is not set, calculate it based on tax + subtotal
            $taxPerc = $billData['tax_id']
                ? Tax::find($billData['tax_id'])->percentage
                : 0;

            return ($i['subtotal'] ?? 0) * (1 + $taxPerc / 100);
        });
        // Convertir fecha (Y-m-d) a datetime agregando la hora actual
        $billDateTime = Carbon::createFromFormat('Y-m-d', $billData['date'])
            ->setTimeFromTimeString(Carbon::now()->toTimeString());
        $newBill = [
            'user_id' => Auth::id(),
            'vendor_id' => $billData['vendor_id'],
            'date' => $billDateTime->format('Y-m-d H:i:s'),
            'tax_id' => $billData['tax_id'] ?? null,
            'subtotal' => $sumSubtotals,
            'total' => $sumTotals,
            'invoice' => $billData['title'],
            'amount_paid' => 0,
            'balance_remaining' => $sumTotals,
            'status' => 0, // 0 for unpaid
            'vendor' => Vendor::find($billData['vendor_id'])->vendor ?? null,
            'tax' => Tax::find($billData['tax_id'])->percentage ?? 0,
        ];
        // start transaction
        DB::beginTransaction();
        try {

            $user = $request->user();
            $company = $user->company;
            $shops = $company?->shops;

            $firstShop = $shops[0] ?? null;

            $shopId = $firstShop ? $firstShop->id : null;

            $itemsCreated = [];
            foreach ($itemsData as $item) {
                $item['user_id'] = Auth::user()->id;
                $item['shop_id'] = $shopId;

                // Establecer type por defecto si es null
                if (empty($item['type'])) {
                    $item['type'] = 'device';
                }

                $itemsCreated[] = Item::create($item);
            }
            $createdBill = Bill::create($newBill);

            DB::commit();

            return response()->json([
                'bill' => $createdBill,
                'items' => $itemsCreated,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al crear Bill + Items',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Item  $item
     */
    public function edit($item): \Inertia\Response
    {
        $user = Auth::user();
        $customFields = CustomField::where('user_id', $user->id)->get();
        $ids = base64_decode(urldecode($item));
        $ids = explode(';', $ids);
        $items = Item::whereIn('id', $ids)->get();

        return Inertia::render('Inventory/Edit', [
            'editing' => $items,
            'fields' => $customFields,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $items = $request->input('items');
        $updated = [];

        foreach ($items as $item) {
            $item['user_id'] = Auth::user()->id;

            $object = Item::updateOrCreate(['id' => @$item['id']], $item);
            $updated[] = $object;
        }

        return response()->json($updated);
    }

    /**
     * Deletes multiple resource.
     */
    public function obliterate(Request $request): \Illuminate\Http\JsonResponse
    {
        $items = collect($request->input())->pluck('id');
        $tabitem = TabItem::whereIn('item_id', $items->toArray())->delete();
        $tabhistory = TabHistory::whereIn('item_id', $items->toArray())->delete();
        $deleted = Item::destroy($items->toArray());

        return response()->json($deleted);
    }

    /**
     * Updates multiple resource.
     */
    public function correct(Request $request): \Inertia\Response
    {
        $ids = collect($request->input())->toArray();
        $items = Item::whereIn('id', $ids)->get();

        return Inertia::render('Items/CreateEdit', [
            'editing' => $items,
        ]);
    }

    /**
     * Generates a pdf label for a single resource.
     */
    public function label(Item $item): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('label', compact('item'))->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => 'true'])->setPaper('a5', 'portrait');

        return $pdf->stream();
    }

    public function getLabels(string $items): \Illuminate\Http\Response
    {
        // “1,2,5” → [1,2,5]
        $ids = array_filter(explode(',', $items), fn ($id) => is_numeric($id));
        $records = Item::whereIn('id', $ids)->get();

        // Cargamos una vista que incluye cada etiqueta + salto de página
        $pdf = Pdf::loadView('labels', compact('records'));

        return $pdf->stream('labels.pdf');
    }

    public function getLabelsNewItems(Request $request): \Illuminate\Http\Response
    {
        $data = $request->validate([
            'records' => 'required|array',
            // etc.
        ]);
        Log::info('Generating labels for new items', ['data' => $data]);
        // Usa directamente los datos de draft items, sin buscar en DB
        $items = collect($data['records'])->map(function ($r) {
            return (object) $r;
        });

        Log::info('Generating labels for new items', ['records' => $items]);
        // PASA un array asociativo:
        $pdf = Pdf::loadView('labels', ['records' => $items]);

        return $pdf->stream('labels.pdf');
    }

    /**
     * Puts on hold every given item.
     */
    public function hold(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = collect($request->input('data'))->pluck('id');
        $customer = $request->input('customer');
        $tabItems = TabItem::whereIn('item_id', $ids)->get();
        foreach ($tabItems as $tI) {
            $tabHistory = new TabHistory;
            $tabHistory->tab_id = $tI->tab_id;
            $tabHistory->item_id = $tI->item_id;
            $tabHistory->save();
        }
        $removeTabItem = TabItem::whereIn('item_id', $ids)->delete();
        $items = Item::whereIn('id', $ids)
            ->with(['storage:id,name,limit'])
            ->update(['hold' => Carbon::now(), 'customer' => $customer]);

        return response()->json($items);
    }

    /**
     * removes on hold every given item.
     */
    public function unhold(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = collect($request->input('data'))->pluck('id');
        $items = Item::whereIn('id', $ids)->update(['hold' => null]);

        $tabHistories = TabHistory::whereIn('item_id', $ids)->get();
        foreach ($tabHistories as $tH) {
            $tabItem = new TabItem;
            $tabItem->tab_id = $tH->tab_id;
            $tabItem->item_id = $tH->item_id;
            $tabItem->save();
        }

        return response()->json($items);
    }

    /**
     * Return a list of resources on hold
     *
     * @return \Illuminate\Http\Response
     */
    public function viewHold(): \Inertia\Response
    {
        $user = Auth::user();
        $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
        $customFields = CustomField::where('user_id', $user->id)->get();

        // Con el nuevo scope global, ya no necesitamos filtrar por user_id
        $context = [
            'items' => Item::with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNull('sold')->whereNotNull('hold')->get(),
            'tabs' => $tabs,
            'fields' => $customFields,
        ];

        return Inertia::render('Inventory/Hold', $context);
    }

    public function tabStore(Request $request)
    {

        $user = Auth::user();

        $latesttab = Tab::orderBy('order', 'desc')->first();

        if ($latesttab) {
            $neworder = $latesttab->order + 1;
        } else {
            $neworder = 1;
        }

        $tab = $request->tab;
        $tabenter = new Tab;
        $tabenter->user_id = $user->id;
        $tabenter->name = $tab;
        $tabenter->order = $neworder;
        $tabenter->save();

        $tabs = Tab::where('user_id', $user->id)->get();
        // if (Auth::user()->role == 'USER') {
        //     $context = [
        //         'items' => $user->store ? Item::where("supplier", $user->store->name)->whereNull("sold")->whereNotNull("hold")->get() : '',
        //     ];
        // } else {
        //     $context = [
        //         'items' => Item::whereNull("sold")->whereNotNull("hold")->get(),
        //     ];
        // }
        // dd($id);

        $context = [
            'tabs' => $tabs,
        ];

        return response()->json($context);
    }

    public function tabMove(Request $request)
    {
        try {
            $tab = $request->tab;
            $itemId = $request->item;

            // Clear the hold status when moving an item to a tab
            $item = Item::find($itemId);
            if ($item && $item->hold !== null) {
                $item->hold = null;
                $item->save();
            }

            $tabitem = TabItem::where('item_id', $itemId)->first();
            if ($tabitem) {
                $tabitem->item_id = $itemId;
                $tabitem->tab_id = $tab;
                $tabitem->save(['item_id', 'tab_id']);
            } else {
                $tabitem = new TabItem;
                $tabitem->item_id = $itemId;
                $tabitem->tab_id = $tab;
                $tabitem->save();
            }

            return response()->json($tabitem, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function tabMoveAll(Request $request)
    {
        $items = collect($request->input('item'))->pluck('id');
        $tab = $request->tab;
        foreach ($items as $item) {
            $check = TabItem::where('item_id', $item)->where('tab_id', $tab)->count();
            if ($check) {
            } else {
                $tabitem = new TabItem;
                $tabitem->item_id = $item;
                $tabitem->tab_id = $tab;
                $tabitem->save();
            }
        }

        // if (Auth::user()->role == 'USER') {
        //     $context = [
        //         'items' => $user->store ? Item::where("supplier", $user->store->name)->whereNull("sold")->whereNotNull("hold")->get() : '',
        //     ];
        // } else {
        //     $context = [
        //         'items' => Item::whereNull("sold")->whereNotNull("hold")->get(),
        //     ];
        // }
        // dd($id);

        return response()->json('success');
    }

    /**
     * Return Item to Active Inventory
     */
    public function tabreturnmove(Request $request)
    {
        try {
            $user = Auth::user();
            $tabsid = Tab::where('user_id', $user->id)->orderBy('id', 'desc')->pluck('id');
            $tabtemremove = TabItem::where('tab_id', $request->tab_id)->whereIn('item_id', $request->item_ids)->delete();
            $tabhistoryremove = TabHistory::where('tab_id', $request->tab_id)->whereIn('item_id', $request->item_ids)->delete();

            return response()->json($tabtemremove, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function tabReorder(Request $request)
    {
        $user = Auth::user();
        $taborder = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->pluck('order');
        foreach ($request->tab as $key => $tab) {
            $tabreorder = Tab::where('id', $tab['id'])->update(['order' => $taborder[$key]]);
        }

        // dd('yes');
        return response()->json('success');
    }

    public function tabRemove(Request $request)
    {
        $user = Auth::user();
        $tab = Tab::where('id', $request->id)->first();
        $tabItems = TabItem::where('tab_id', $tab->id)->delete();
        $tabHistory = TabHistory::where('tab_id', $tab->id)->delete();
        $tab->delete();

        $tabs = Tab::where('user_id', $user->id)->get();

        $context = [
            'tabs' => $tabs,
        ];

        return response()->json($context);
    }

    public function tabItems($id)
    {

        $user = Auth::user();

        $tabs = Tab::where('user_id', $user->id)->get();
        // if (Auth::user()->role == 'USER') {
        //     $context = [
        //         'items' => $user->store ? Item::where("supplier", $user->store->name)->whereNull("sold")->whereNotNull("hold")->get() : '',
        //     ];
        // } else {
        //     $context = [
        //         'items' => Item::whereNull("sold")->whereNotNull("hold")->get(),
        //     ];
        // }
        // dd($id);
        $items = Item::whereHas('tabItems', function ($q) use ($id) {
            $q->where('tab_id', $id);
        })->with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNull('sold')->whereNull('hold');

        $customFields = CustomField::where('user_id', $user->id)->get();

        // Con el nuevo scope global, ya no necesitamos filtrar por user_id
        $context = [
            'items' => $items->get(),
            'tabs' => $tabs,
            'current_tab' => $id,
            'fields' => $customFields,
        ];

        $checktab = Tab::where('id', $id)->first();
        // dd($context);
        if ($checktab->user_id == $user->id) {
            return Inertia::render('Inventory/Tab', $context);
        }
    }

    public function refundItem(Request $request)
    {
        try {
            $itemArray = $request->input('item');
            $item = Item::find($itemArray['id']);
            $customer = Customer::where('customer', $item->customer)->first();
            $sale = Sale::find($item->sale_id);
            $value = $item->selling_price - $sale->discount;
            // Subtotal Calculation
            $subtotal = $sale->subtotal - $value;
            // Tax Calculation
            $tax = $value * ($sale->tax / 100);
            // Total Calculation
            $total = $sale->total - ($value + $tax);
            // Balance Calculation
            $balance = $sale->balance_remaining - ($value + $tax);
            $balance = $balance <= 0 ? 0 : $balance;
            // Flat Tax
            $flatTax = $sale->flatTax - $tax;
            $paid = 0;
            if ($sale->amount_paid >= $total) {
                $paid = 1;
            } else {
                $paid = 0;
            }
            Customer::where('id', $customer->id)->update([
                'credit' => $item->selling_price + $customer->credit,
            ]);

            ReturnItems::create([
                'item' => $item->id,
                'customer' => $customer->id,
                'credit' => $item->selling_price,
                'model' => $item->model,
                'imei' => $item->imei,
            ]);

            Sale::where('id', $item->sale_id)->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'flatTax' => $flatTax,
                'balance_remaining' => $balance,
                'paid' => $paid,
            ]);

            if ($item->removeSale()) {
                return response()->json($subtotal);
            } else {
                return response()->json($subtotal);
            }
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Removes a resource from a Sale and back into the inventory
     */
    public function returnItem(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            if ($request->item) {
                $request->selectedItems = [];
                $request->selectedItems[] = $request->item;
            }

            foreach ($request->selectedItems as $item) {
                $item = Item::find($item['id']);
                $sale = Sale::find($item->sale_id);
                if ($sale != null) {
                    $value = $item->selling_price - $sale->discount;
                    // Subtotal Calculation
                    $subtotal = $sale->subtotal - $value;
                    // Tax Calculation
                    $tax = $value * ($sale->tax / 100);
                    // Total Calculation
                    $total = $sale->total - ($value + $tax);
                    // Balance Calculation
                    $balance = $sale->balance_remaining - ($value + $tax);
                    $balance = $balance <= 0 ? 0 : $balance;
                    // Flat Tax
                    $flatTax = $sale->flatTax - $tax;
                    $paid = 0;
                    if ($sale->amount_paid >= $total) {
                        $paid = 1;
                    } else {
                        $paid = 0;
                    }

                    Sale::where('id', $item->sale_id)->update([
                        'subtotal' => $subtotal,
                        'total' => $total,
                        'flatTax' => $flatTax,
                        'balance_remaining' => $balance,
                        'paid' => $paid,
                    ]);
                }

                if ($item->user_id == null) {
                    $item->user_id = Auth::user()->id;
                    $item->save(['user_id']);
                }

                $item->removeSale();
            }

            return response()->json($request->selectedItem, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function excelCreate()
    {
        return Inertia::render('Inventory/BulkCreate/ItemsBulkCreate');
    }

    public function excelStore(ItemExcelForm $request)
    {
        try {
            $check = $request->validated();
            $test = Excel::import(new ItemsImport, $check['file']);

            return response()->json($test, 201);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function excelDemoDownload()
    {
        $user = Auth::user();
        $vendors = Vendor::where('user_id', $user->id)
            ->selectRaw('CONCAT(vendor, "#", id) as formatted_vendor')
            ->pluck('formatted_vendor')
            ->toArray();

        return Excel::download(new ItemsExample($vendors), 'items.xlsx');
    }

    /**
     * Global search: incluye items activos, vendidos y en hold.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $user = Auth::user();
        $limit = (int) $request->query('limit', 10);

        // Con el nuevo scope global, ya no necesitamos filtrar por user_id
        $items = Item::with(['storage:id,name', 'vendor:id,vendor'])
            ->when($q, fn ($query) => $query->where(function ($q2) use ($q) {
                $q2->where('model', 'like', "%{$q}%")
                    ->orWhere('imei', 'like', "%{$q}%")
                    ->orWhere('colour', 'like', "%{$q}%");
            }))
            ->limit($limit)
            ->get();

        return response()->json($items);
    }

    /**
     * Generate selling prices for provided items (placeholder logic).
     * Accepts payload: { items: [ { id?, imei?, cost?, ... } ] }
     * Returns: { items: [ ...with selling_price set... ] }
     */
    public function generateSellingPrice(Request $request)
    {
        $data = $request->input('items', []);
        if (! is_array($data)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $updated = [];

        foreach ($data as $item) {
            $model = isset($item['model']) ? trim($item['model']) : null;
            $battery = isset($item['battery']) ? trim($item['battery']) : null;
            $grade = isset($item['grade']) ? trim($item['grade']) : null;
            $issues = isset($item['issues']) ? trim($item['issues']) : null;

            $foundPrice = null;
            Log::info('generateSellingPrice for item: ', [
                'model' => $model,
                'battery' => $battery,
                'grade' => $grade,
                'issues' => $issues,
            ]);
            // base query: items that have a selling_price and are active (not sold / not on hold)
            // and only from the last 6 months (by created_at or updated_at)
            $sixMonthsAgo = now()->subMonths(6);
            $baseQuery = Item::whereNotNull('selling_price')
                ->where(function ($query) use ($sixMonthsAgo) {
                    $query->where('created_at', '>=', $sixMonthsAgo)
                        ->orWhere('updated_at', '>=', $sixMonthsAgo);
                });
            // If the incoming item has no `issues` value (null), restrict matches
            // to items that also have `issues` set to null (exclude items with any issues).
            if ($issues === null) {
                $baseQuery->whereNull('issues');
            }

            // Build dynamic list of available fields for this item
            $available = [];
            if ($model) {
                $available[] = 'model';
            }
            if ($battery) {
                $available[] = 'battery';
            }
            if ($grade) {
                $available[] = 'grade';
            }
            if ($issues) {
                $available[] = 'issues';
            }

            // No available fields -> cannot match
            if (! empty($available)) {
                // Search using exactly the fields that the incoming item provides
                $q = clone $baseQuery;

                // Apply filters for all available fields (exact match based on what the item brings)
                foreach ($available as $field) {
                    // Special handling for battery: use range-based numeric comparisons
                    if ($field === 'battery') {
                        $batteryValue = ${$field};
                        // try to extract numeric portion (e.g., "85%" or "85")
                        $num = null;
                        if (is_numeric($batteryValue)) {
                            $num = (int) $batteryValue;
                        } else {
                            // strip non-digits
                            preg_match('/(\d{1,3})/', $batteryValue ?? '', $m);
                            if (! empty($m[1])) {
                                $num = (int) $m[1];
                            }
                        }

                        if ($num === null) {
                            // fallback to LIKE if we cannot parse a number
                            $q->where($field, 'like', '%'.${$field}.'%');
                        } else {
                            if ($num <= 79) {
                                $q->where($field, '<=', 79);
                            } elseif ($num >= 85) {
                                $q->where($field, '>=', 85);
                            } else {
                                // 80-84 range
                                $q->whereBetween($field, [80, 84]);
                            }
                        }
                    } else {
                        $value = ${$field};
                        $q->where($field, 'like', '%'.$value.'%');
                    }
                }

                // Clone the query before consuming it with first()/get()
                $qForMatch = (clone $q)
                    ->orderByRaw('CASE WHEN sold IS NULL THEN 0 ELSE 1 END ASC')
                    ->orderByDesc('date');

                $qForList = (clone $qForMatch);

                $match = $qForMatch->first(['id', 'selling_price', 'model']);
                $completelist = $qForList->get(['id', 'selling_price', 'model', 'battery', 'grade', 'issues', 'created_at', 'updated_at', 'sold']);

                Log::info('generateSellingPrice all matches found', [
                    'total_matches' => $completelist->count(),
                    'matches' => $completelist->toArray(),
                ]);

                if ($match) {
                    $foundPrice = round(floatval($match->selling_price), 2);
                    Log::info('generateSellingPrice match selected', [
                        'model' => $match->model ?? null,
                        'selling_price' => $foundPrice,
                        'fields_used' => implode('+', $available),
                        'matched_item_id' => $match->id ?? null,
                    ]);
                } else {
                    Log::info('generateSellingPrice no match found', [
                        'input_item_id' => $item['id'] ?? null,
                        'tried_fields' => implode('+', $available),
                    ]);
                }
            }

            // If no similar matches are found, $foundPrice remains null
            $item['selling_price'] = $foundPrice;
            $updated[] = $item;
        }

        return response()->json(['items' => $updated]);
    }

    /**
     * API: devuelve solo los IDs de los items que pertenecen a la tab indicada.
     *
     * @param  int|string  $id
     */
    public function getTabItems($id): JsonResponse
    {
        try {
            $tab = Tab::find($id);
            if (! $tab) {
                return response()->json(['error' => 'Tab not found'], 404);
            }

            // Usamos JOIN directo en lugar de whereHas para mejor rendimiento
            // y retornamos los items completos con sus relaciones
            $items = Item::select('items.*')
                ->join('tab_items', 'items.id', '=', 'tab_items.item_id')
                ->where('tab_items.tab_id', $id)
                ->whereNull('items.sold')
                ->whereNull('items.hold')
                ->with(['storage:id,name,limit', 'vendor:id,vendor'])
                ->get();

            return response()->json(['items' => $items], 200);
        } catch (\Throwable $e) {
            Log::error('getTabItems error: '.$e->getMessage(), ['tab_id' => $id]);

            return response()->json(['error' => 'Could not retrieve tab items'], 500);
        }
    }
}
