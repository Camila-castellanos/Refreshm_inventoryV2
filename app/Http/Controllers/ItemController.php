<?php

namespace App\Http\Controllers;

use App\Models\Shop;

use App\Http\Requests\ItemForm;
use App\Http\Requests\RequestItemsForm;
use App\Http\Requests\ItemExcelForm;
use App\Models\Item;
use App\Models\Tab;
use App\Models\Models;
use App\Models\Manufacturer;
use App\Models\TabItem;
use App\Models\TabHistory;
use App\Models\CustomField;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\ReturnItems;
use App\Models\Storage;
use App\Mail\RequestItems;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Exports\ItemsExample;
use Illuminate\Support\Facades\Auth;
use App\Imports\ItemsImport;
use App\Exports\ItemDemoExport;
use Illuminate\Support\Facades\Mail;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\ItemsWithBillForm;
use App\Models\Tax;
use App\Models\Bill;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index(): \Inertia\Response
    {
        $user = Auth::user();

        $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
        $customFields = CustomField::where('user_id', $user->id)->get();

        if (Auth::user()->role == ('ADMIN')) {
            $context = [
                'items' => Item::where("user_id", $user->id)
                ->with(['storage' => function ($query) {
                    $query->select('id', 'name', 'limit') // other columns
                    ->withCount('items'); // current count of items in storage
            }, 'vendor:id,vendor'])
            ->whereIn('type', ['device', 'accessory'])
                    ->whereNull("sold")
                    ->whereNull("hold")
                    ->whereNotIn('id', TabItem::pluck('item_id'))
                    ->get(),
                'tabs' => $tabs,
                'fields' => $customFields,
            ];
        } else if (Auth::user()->role == ('USER')) {
            $context = [
                'items' => Item::where("user_id", $user->id)
                ->with(['storage' => function ($query) {
                    $query->select('id', 'name', 'limit') // other columns
                    ->withCount('items'); // current count of items in storage
            }, 'vendor:id,vendor'])
            ->whereIn('type', ['device', 'accessory'])
                    ->whereNull("sold")
                    ->whereNull("hold")
                    ->whereNotIn('id', TabItem::pluck('item_id'))
                    ->get(),
                'tabs' => $tabs,
                'fields' => $customFields,
            ];
        } else {
            $context = [
                'items' => Item::where("user_id", $user->id)
                ->with(['storage' => function ($query) {
                    $query->select('id', 'name', 'limit') // other columns
                    ->withCount('items'); // current count of items in storage
            }, 'vendor:id,vendor'])
            ->whereIn('type', ['device', 'accessory'])
                    ->whereNull("sold")
                    ->whereNull("hold")
                    ->whereNotIn('id', TabItem::pluck('item_id'))
                    ->get(),
                'tabs' => $tabs,
                'fields' => $customFields,
            ];
        }

        $context['customers'] = Customer::all();
        return Inertia::render('Inventory/Index', $context);
    }

    public function getItems(): \Illuminate\Http\JsonResponse
        {
    $user = Auth::user();

    $items = Item::where("user_id", $user->id)
    ->with(['storage' => function ($query) {
        $query->select('id', 'name', 'limit') // other columns
        ->withCount('items'); // current count of items in storage
}, 'vendor:id,vendor'])
->whereIn('type', ['device', 'accessory'])
        ->whereNull("sold")
        ->whereNull("hold")
        ->whereNotIn('id', TabItem::pluck('item_id'))
        ->get();

    return response()->json($items);
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
                    'message' => 'All selected items are already assigned to this storage.'
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
                    'position' => $newPosition
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
     *
     * @return \Inertia\Response
     */
    public function public(): \Inertia\Response
    {
        $role = "GUEST";

        if (Auth::user() != null) {
            $role = Auth::user()->role;
        }

        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabs = Tab::where('user_id', $user)->orderBy('order', 'asc')->get();

        $items = Item::where("user_id", $user)
            ->whereNull("sold")
            ->whereIn('type', ['device', 'accessory'])
            ->whereNull("hold")
            ->get();

        $items = Item::where('user_id', $user)->whereNull('sold')->whereNull('hold')->get();
        $count = 0;
        $manufacturers = [];

        foreach ($items as $item) {
            $manufacturerName = trim($item->manufacturer);
            if (!empty($manufacturerName)) {
                $count++;
                $manufacturerExists = false;
                foreach ($manufacturers as $existingManufacturer) {
                    if ($existingManufacturer["name"] === $manufacturerName) {
                        $manufacturerExists = true;
                        break;
                    }
                }
                // If the manufacturer doesn't exist, add it to the $manufacturers array
                if (!$manufacturerExists) {
                    $count++; // Increments the count
                    $manufacturers[] = [
                        "id" => $count, // Assigns the value of the variable $count to the key "id"
                        "name" => $manufacturerName, // Assigns the value of the variable $manufacturerName to the key "name"
                    ];
                }
            }
            if ($item->model && strpos($item->model, 'Housing') !== false) {
                $item->tab = "Housing";
            } else if ($item->model && strpos($item->model, 'Digitizer') !== false) {
                $item->tab = "Digitizer";
            } else {
                $tabItem = TabItem::where('item_id', $item->id)->get();
                if (!$tabItem->isEmpty()) {
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
        $form = $request;
        $name = $form["name"];
        $email = $form["email"];
        $store = $form["store"];
        $notes = $form["notes"];
        $items = $form["items"];

        $uniqueItems = collect($items)->unique('id')->values()->all();



        $mail = Mail::to('will@refreshmobile.ca')->send(new RequestItems($name, $email, $store, $notes, $uniqueItems));
        return response()->json($mail, 201);
     
    }

    public function lcd()
    {
        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabs = Tab::where('user_id', $user)->orderBy('order', 'asc')->get();
        $searchTerm = "Digitizer";

        $context = [
            'items' => Item::where("user_id", $user)->whereNull("sold")->whereNull("hold")->where("user_id", $user)->where("model", "LIKE", "%" . $searchTerm . "%")->get(),
        ];

        return response()->json($context);
    }

    public function housing()
    {
        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabs = Tab::where('user_id', $user)->orderBy('order', 'asc')->get();
        $searchTerm = "Housing";

        $context = [
            'items' => Item::where("user_id", $user)->whereNull("sold")->whereNull("hold")->where("model", "LIKE", "%" . $searchTerm . "%")->get(),
        ];

        return response()->json($context);
    }

    public function customTab($id)
    {
        $user = User::where('role', 'OWNER')->pluck('id')->toarray();
        $tabItems = TabItem::where('tab_id', $id)->pluck('item_id');
        $context = [
            'items' => Item::whereNull("sold")->whereNull("hold")->whereIn("id", $tabItems)->get(),
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
        return response()->json(Item::whereNull("sold")->whereNull("hold")->get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create(): \Inertia\Response
    {
        $user = Auth::user();
        $customFields = CustomField::where('user_id', $user->id)->get();
        return Inertia::render("Items/CreateEdit", ['fields' => $customFields]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ItemForm $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ItemForm $request): \Illuminate\Http\JsonResponse
    {
        $items = $request->validated();

        $user = $request->user(); 
        $company = $user->company;
        $shops = $company?->shops;

        $firstShop = $shops[0] ?? null;

        $shopId = $firstShop ? $firstShop->id : null;

        $created = [];
        foreach ($items["items"] as $item) {
            $item['user_id'] = Auth::user()->id;
            $item['shop_id'] = $shopId;
            $created[] = Item::create($item);
        }
        return response()->json($created, 201);
    }

    public function storeWithBill(ItemsWithBillForm $request)
    {
        // validate and extract items data and bill data
        ['bill' => $billData, 'items' => $itemsData] = $request->validated();
        
         // sum subtotals of items
        $sumSubtotals = collect($itemsData)->sum(fn($i) => $i['subtotal'] ?? 0);

        // sum of totals of items
        $sumTotals = collect($itemsData)->sum(function($i) use ($billData) {
        if (isset($i['cost'])) {
            return $i['cost'];
        }
        // if cost is not set, calculate it based on tax + subtotal
        $taxPerc = $billData['tax_id']
            ? Tax::find($billData['tax_id'])->percentage
            : 0;
        return ($i['subtotal'] ?? 0) * (1 + $taxPerc / 100);
    });
     $newBill = [
            'user_id' => Auth::id(),
            'vendor_id' => $billData['vendor_id'],
            'date' => $billData['date'],
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
            $itemsCreated[] = Item::create($item);
        }
        $createdBill = Bill::create($newBill);

        DB::commit();

        return response()->json([
            'bill'  => $createdBill,
            'items' => $itemsCreated,
        ], 201);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
                'error'   => 'Error al crear Bill + Items',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Inertia\Response
     */
    public function edit($item): \Inertia\Response
    {
        $user = Auth::user();
        $customFields = CustomField::where('user_id', $user->id)->get();
        $ids = base64_decode(urldecode($item));
        $ids = explode(";", $ids);
        $items = Item::whereIn("id", $ids)->get();
        return Inertia::render('Inventory/Edit', [
            "editing" => $items,
            "fields" => $customFields,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $items = $request->input("items");
        $updated = [];

        foreach ($items as $item) {
            $item['user_id'] = Auth::user()->id;

            $object = Item::updateOrCreate(['id' => @$item["id"]], $item);
            $updated[] = $object;
        }

        return response()->json($updated);
    }


    /**
     * Deletes multiple resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function obliterate(Request $request): \Illuminate\Http\JsonResponse
    {
        $items = collect($request->input())->pluck("id");
        $tabitem = TabItem::whereIn('item_id', $items->toArray())->delete();
        $tabhistory = TabHistory::whereIn('item_id', $items->toArray())->delete();
        $deleted = Item::destroy($items->toArray());
        return response()->json($deleted);
    }

    /**
     * Updates multiple resource.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function correct(Request $request): \Inertia\Response
    {
        $ids = collect($request->input())->toArray();
        $items = Item::whereIn("id", $ids)->get();
        return Inertia::render('Items/CreateEdit', [
            "editing" => $items
        ]);
    }


    /**
     * Generates a pdf label for a single resource.
     *
     * @param Item $item
     * @return \Illuminate\Http\Response
     */
    public function label(Item $item): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView("label", compact("item"))->setOptions(["defaultFont" => "sans-serif", "isRemoteEnabled" => "true"])->setPaper("a5", "portrait");
        return $pdf->stream();
    }

    public function getLabels(string $items): \Illuminate\Http\Response
{
    // “1,2,5” → [1,2,5]
    $ids = array_filter(explode(',', $items), fn($id) => is_numeric($id));
    $records = Item::whereIn('id', $ids)->get();

    // Cargamos una vista que incluye cada etiqueta + salto de página
    $pdf = Pdf::loadView('labels', compact('records'));

    return $pdf->stream('labels.pdf');
}

public function getLabelsNewItems(Request $request): \Illuminate\Http\Response
{
    $data = $request->validate([
      'records'       => 'required|array',
      // etc.
    ]);

    // Reconstruye la colección de Items (con sus relaciones si quieres)
    $items = collect($data['records'])->map(function($r) {
        // load existing items with storage & vendor, or wrap new items as object
        $item = isset($r['id'])
            ? Item::with(['storage','vendor'])->find($r['id'])
            : (object)$r;
        // resolve vendor name by vendor_id
        $vendorId = $item->vendor_id ?? $r['vendor_id'] ?? null;
        $item->vendor = $vendorId
            ? optional(Vendor::find($vendorId))
            : null;
        return $item;
    })->filter();

    Log::info("Generating labels for new items", ['records' => $items]);
    // PASA un array asociativo:
    $pdf = Pdf::loadView('labels', ['records' => $items]);

    return $pdf->stream('labels.pdf');
}




    /**
     * Puts on hold every given item.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hold(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = collect($request->input("data"))->pluck("id");
        $customer = $request->input("customer");
        $tabItems = TabItem::whereIn('item_id', $ids)->get();
        foreach ($tabItems as $tI) {
            $tabHistory = new TabHistory();
            $tabHistory->tab_id = $tI->tab_id;
            $tabHistory->item_id = $tI->item_id;
            $tabHistory->save();
        }
        $removeTabItem = TabItem::whereIn('item_id', $ids)->delete();
        $items = Item::whereIn("id", $ids)
            ->with(['storage:id,name,limit'])
            ->update(["hold" => Carbon::now(), "customer" => $customer]);

        return response()->json($items);
    }


    /**
     * removes on hold every given item.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unhold(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = collect($request->input("data"))->pluck("id");
        $items = Item::whereIn("id", $ids)->update(["hold" => null]);

        $tabHistories = TabHistory::whereIn('item_id', $ids)->get();
        foreach ($tabHistories as $tH) {
            $tabItem = new TabItem();
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
        // if (Auth::user()->role == 'USER') {
        //     $context = [
        //         'items' => $user->store ? Item::where("supplier", $user->store->name)->whereNull("sold")->whereNotNull("hold")->get() : '',
        //     ];
        // } else {
        //     $context = [
        //         'items' => Item::whereNull("sold")->whereNotNull("hold")->get(),
        //     ];
        // }

        $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
        $customFields = CustomField::where('user_id', $user->id)->get();

        if (Auth::user()->role == ('ADMIN')) {
            $context = [
                // 'items' => $user->store ? Item::where("user_id", $user->id)->get() : '',
                'items' => Item::where("user_id", $user->id)->with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNull("sold")->whereNotNull("hold")->get(),
                'tabs' => $tabs,
                'fields' => $customFields,
            ];
        } else if (Auth::user()->role == ('USER')) {
            $usersId = User::where('store_id', @$user->store_id)->pluck('id')->toarray();
            $context = [
                // 'items' => Item::whereIn("user_id", @$usersId)->whereNull("sold")->whereNotNull("hold")->get(),
                'items' => Item::where("user_id", $user->id)->with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNull("sold")->whereNotNull("hold")->get(),
                'tabs' => $tabs,
                'fields' => $customFields,
            ];
        } else {
            $context = [
                'items' => Item::whereNull("sold")->with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNotNull("hold")->get(),
                'tabs' => $tabs,
                'fields' => $customFields,
            ];
        }



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
            $item = $request->item;
            $tabitem = TabItem::where('item_id', $item)->first();
            if ($tabitem) {
                $tabitem->item_id = $item;
                $tabitem->tab_id = $tab;
                $tabitem->save(['item_id', 'tab_id']);
            } else {
                $tabitem = new TabItem;
                $tabitem->item_id = $item;
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
        $items = collect($request->input('item'))->pluck("id");
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
     *
     *
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

        if (Auth::user()->role == ('ADMIN')) {
            $context = [
                // 'items' => $user->store ? Item::where("user_id", $user->id)->get() : '',
                'items' => $items->where("user_id", $user->id)->get(),
                'tabs' => $tabs,
                'current_tab' => $id,
                'fields' => $customFields,
            ];
        } else if (Auth::user()->role == ('USER')) {
            $usersId = User::where('store_id', @$user->store_id)->pluck('id')->toarray();
            $context = [
                'items' => $items->whereIn("user_id", @$usersId)->get(),
                'tabs' => $tabs,
                'current_tab' => $id,
                'fields' => $customFields,
            ];
        } else {
            $context = [
                // 'items' => $items->get(),
                'items' => $items->where("user_id", $user->id)->get(),
                'tabs' => $tabs,
                'current_tab' => $id,
                'fields' => $customFields,
            ];
        }
        $checktab = Tab::where('id', $id)->first();
        // dd($context);
        if ($checktab->user_id == $user->id) {
            return Inertia::render('Inventory/Tab', $context);
        }
    }

    public function refundItem(Request $request)
    {
        try {
            $itemArray = $request->input("item");
            $item = Item::find($itemArray["id"]);
            $customer = Customer::where('customer', $item->customer)->first();
            $sale = Sale::find($item->sale_id);
            $value = $item->selling_price - $sale->discount;
            //Subtotal Calculation
            $subtotal = $sale->subtotal - $value;
            //Tax Calculation
            $tax = $value * ($sale->tax / 100);
            //Total Calculation
            $total = $sale->total - ($value + $tax);
            //Balance Calculation
            $balance = $sale->balance_remaining - ($value + $tax);
            $balance = $balance <= 0 ? 0 : $balance;
            //Flat Tax
            $flatTax = $sale->flatTax - $tax;
            $paid = 0;
            if ($sale->amount_paid >= $total) {
                $paid = 1;
            } else {
                $paid = 0;
            }
            Customer::where("id", $customer->id)->update([
                "credit" => $item->selling_price + $customer->credit,
            ]);

            ReturnItems::create([
                "item" => $item->id,
                "customer" => $customer->id,
                "credit" => $item->selling_price,
                "model" => $item->model,
                "imei" => $item->imei,
            ]);

            Item::where("id", $item->id)->update([
                "sale_id" => null,
                "sold" => null,
            ]);

            Sale::where("id", $item->sale_id)->update([
                "subtotal" => $subtotal,
                "total" => $total,
                "flatTax" => $flatTax,
                "balance_remaining" => $balance,
                "paid" => $paid,
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
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
                    //Subtotal Calculation
                    $subtotal = $sale->subtotal - $value;
                    //Tax Calculation
                    $tax = $value * ($sale->tax / 100);
                    //Total Calculation
                    $total = $sale->total - ($value + $tax);
                    //Balance Calculation
                    $balance = $sale->balance_remaining - ($value + $tax);
                    $balance = $balance <= 0 ? 0 : $balance;
                    //Flat Tax
                    $flatTax = $sale->flatTax - $tax;
                    $paid = 0;
                    if ($sale->amount_paid >= $total) {
                        $paid = 1;
                    } else {
                        $paid = 0;
                    }

                    Sale::where("id", $item->sale_id)->update([
                        "subtotal" => $subtotal,
                        "total" => $total,
                        "flatTax" => $flatTax,
                        "balance_remaining" => $balance,
                        "paid" => $paid,
                    ]);
                }


                if ($item->user_id == NULL) {
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
        return Inertia::render("Inventory/BulkCreate/ItemsBulkCreate");
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
        $q    = $request->query('q', '');
        $user = Auth::user();
        $limit = (int) $request->query('limit', 10);

        $items = Item::with(['storage:id,name', 'vendor:id,vendor'])
            ->where('user_id', $user->id)
            ->when($q, fn($query) => $query->where(function($q2) use ($q) {
                $q2->where('model',  'like', "%{$q}%")
                   ->orWhere('imei',   'like', "%{$q}%")
                   ->orWhere('colour', 'like', "%{$q}%");
            }))
            ->limit($limit)
            ->get();

        return response()->json($items);
    }
}
