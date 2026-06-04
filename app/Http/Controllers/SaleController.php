<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleForm;
use App\Http\Requests\SaleFormEdit;
use App\Models\CashOnHand;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Storage;
use App\Models\Store;
use App\Models\Tab;
use App\Models\TabItem;
use App\Models\User;
use App\Services\DashboardCacheService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    protected DashboardCacheService $cacheService;

    public function __construct(DashboardCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SaleForm $request)
    {
        $form = $request->validated();
        $form['user_id'] = Auth::user()->id;
        $form['balance_remaining'] = $request->balance_remaining;
        // Convertir payment_date (Y-m-d) a datetime con hora actual y asignar al form
        $paymentDateTime = Carbon::createFromFormat('Y-m-d', $request->payment_date)
            ->setTimeFromTimeString(Carbon::now()->toTimeString());
        $form['date'] = $paymentDateTime->format('Y-m-d H:i:s');

        // NUEVA LÓGICA: Calcular total en el backend antes de crear la venta
        // Fórmula:
        // 1. Tax = (subtotal - crédito) * porcentaje / 100
        // 2. Total = subtotal - (crédito - tax)
        $subtotal = (float) $form['subtotal'];
        $credit = max(0.0, (float) ($form['credit'] ?? 0));
        $taxPercentage = (float) $form['tax'];

        // Calcular tax: (subtotal - crédito) * porcentaje / 100
        $subtotalAfterCredit = max(0.0, $subtotal - $credit);
        $calculatedTax = ($subtotalAfterCredit * $taxPercentage) / 100;

        // Total final: subtotal - (crédito - tax), si es negativo queda en 0
        $creditMinusTax = $credit - $calculatedTax;
        $calculatedTotal = max(0.0, $subtotal - $creditMinusTax);

        // Actualizar el form con los valores calculados
        $form['flatTax'] = round($calculatedTax, 2);
        $form['total'] = round($calculatedTotal, 2);

        // Calculate balance_remaining based on calculated total and amount paid
        $amountPaidStore = max(0.0, (float) ($request->amount_paid ?? 0));
        $form['balance_remaining'] = round($calculatedTotal - $amountPaidStore, 2);
        if ($form['balance_remaining'] < 0) {
            $form['balance_remaining'] = 0;
        }

        // Crear la venta con datetime completo
        $sale = Sale::create($form);

        // Manejar lógica de crédito
        $creditAdded = (float) ($request->credit_added ?? 0);
        $totalCredit = (float) ($request->credit ?? 0);

        // Si hay crédito agregado, actualizar el crédito del cliente
        if ($creditAdded > 0) {
            // Buscar el customer ID desde los items o newItems
            $customerId = null;

            if (! empty($form['items'])) {
                $customerId = $form['items'][0]['customer'] ?? null;
            } elseif (! empty($request->newItems)) {
                $customerId = $request->newItems[0]['customer'] ?? null;
            }

            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    // Restar el crédito agregado del cliente
                    $newCustomerCredit = $customer->credit - $creditAdded;
                    Customer::where('id', $customer->id)->update([
                        'credit' => max(0, $newCustomerCredit), // Asegurar que no sea negativo
                    ]);
                } else {
                    // Customer not found logic if needed
                }
            } else {
                // No customer ID found logic if needed
            }
        }

        // Process existing items (items that were already in inventory)
        $items = $form['items'] ?? [];
        foreach ($items as $sale_item) {
            $item = Item::find($sale_item['id']);

            if ($item && $item->status === Item::STATUS_AVAILABLE) {
                $sale_item['sale_id'] = $sale->id;
                $sale_item['type'] = $sale_item['type'];
                // Capture location from DB before it's potentially cleared
                $sale_item['sold_position'] = $item->position;
                $sale_item['sold_storage_id'] = $item->storage_id;
                $sale_item['sold_storage_name'] = $item->storage?->name;
                unset($sale_item['selected']);

                if ($request->paid) {
                    $sale_item['status'] = Item::STATUS_SOLD;
                    $sale_item['sold'] = Carbon::now();
                    $sale_item['position'] = null;
                    $sale_item['storage_id'] = null;
                } else {
                    $sale_item['status'] = Item::STATUS_RESERVED;
                    $sale_item['sold'] = null;
                }

                $item->update($sale_item);
            }

            if ($request->paid == 1) {
                Payment::insert([
                    'sale_id' => $sale->id,
                    'amount_paid' => $form['total'],
                    'balance_remaining' => $form['balance_remaining'],
                    'payment_method' => $form['payment_method'],
                    'payment_account' => $form['payment_account'],
                    'payment_date' => $paymentDateTime->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            TabItem::where('item_id', $sale_item['id'])->delete();
        }

        if ($request->paid == 1) {
            if ($request->payment_account == 'Cash on Hand') {
                $old_cash = CashOnHand::where('user_id', $form['user_id'])->value('balance');
                CashOnHand::where('user_id', $form['user_id'])->update([
                    'balance' => $old_cash + $form['total'],
                ]);
            }
        }

        // Process new items (items created during the sale)
        if ($request->newItems) {
            foreach ($request->newItems as $new_item) {
                $total = $new_item['selling_price'] + (($form['tax'] / 100) * $new_item['selling_price']);

                $itemData = [
                    'date' => $request->payment_date,
                    'type' => $new_item['type'] ?? 'device',
                    'model' => $new_item['model'] ?? 'Unknown',
                    'issues' => $new_item['issues'] ?? null,
                    'imei' => $new_item['imei'] ?? null,
                    'selling_price' => $new_item['selling_price'] ?? 0,
                    'sale_id' => $sale->id,
                    'customer' => $new_item['customer'] ?? null,
                    'discount' => $request->discount ?? 0,
                    'tax' => $request->tax ?? 0,
                    'user_id' => $form['user_id'],
                    'profit' => $new_item['profit'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($request->paid) {
                    $itemData['status'] = Item::STATUS_SOLD;
                    $itemData['sold'] = Carbon::now();
                } else {
                    $itemData['status'] = Item::STATUS_RESERVED;
                    $itemData['sold'] = null;
                }

                // Auto-assign storage so the Item saving hook (Item.php:347-356)
                // can snapshot a real sold_storage_* triplet when the item is SOLD.
                // For unpaid items, the creating hook (Item.php:370-377) uses the
                // storage_id to assign a position.
                $storageSlot = Storage::findFirstAvailablePosition();
                if ($storageSlot !== null) {
                    $itemData['storage_id'] = $storageSlot['storage_id'];
                    $itemData['position'] = $storageSlot['position'];
                } else {
                    Log::warning('No storage available for new sale item', [
                        'user_id' => Auth::id(),
                        'sale_id' => $sale->id,
                    ]);
                }

                $item = Item::create($itemData);

                if ($request->paid) {
                    Payment::insert([
                        'sale_id' => $sale->id,
                        'amount_paid' => $form['total'],
                        'balance_remaining' => $form['balance_remaining'] ?? 0,
                        'payment_method' => $form['payment_method'] ?? 'cash',
                        'payment_account' => $form['payment_account'] ?? '',
                        'payment_date' => $paymentDateTime->format('Y-m-d H:i:s'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if (($request->payment_account ?? null) == 'Cash on Hand') {
                        $old_cash = CashOnHand::where('user_id', $form['user_id'])->value('balance');
                        CashOnHand::where('user_id', $form['user_id'])->update([
                            'balance' => $old_cash + $form['total'],
                        ]);
                    }
                }
            }
        }

        $receiptUrl = route('sales.receipt', $sale);

        // Invalidate dashboard cache for the authenticated user
        $this->cacheService->invalidateForUser(Auth::id());

        return response()->json($receiptUrl, 201);
    }

    /**
     *  Returns JSON listing the updates on sale
     *  date range ($start - $end).
     *
     * @param  SaleForm  $request
     * @return JsonResponse
     */
    public function update(SaleFormEdit $request)
    {
        try {
            $validated = $request->validated();
            $sale = Sale::find($validated['id']);
            $user = Auth::user();

            // 1. Manejar crédito usando SOLO el delta enviado (credit_added)
            // credit_added puede ser positivo (agrega) o negativo (remueve)
            $creditAdded = (float) ($request->credit_added ?? 0.0);
            $currentSaleCredit = max(0.0, (float) $sale->credit);
            $finalCredit = max(0.0, $currentSaleCredit + $creditAdded);

            // Si hay crédito agregado, actualizar el crédito del cliente
            if ($creditAdded != 0.0) {
                $customer = Customer::where('customer', $request->customer)->first();
                if ($customer) {
                    // Ajustar el crédito del cliente por el delta (positivos restan, negativos suman)
                    $newCustomerCredit = $customer->credit - $creditAdded;
                    Customer::where('id', $customer->id)->update([
                        'credit' => max(0, $newCustomerCredit), // Asegurar que no sea negativo
                    ]);
                }
            }

            // Si NO se envía credit_added pero cambia el total, usar diferencia total como delta
            if (! $request->has('credit_added') && $request->has('credit') && $request->credit != $currentSaleCredit) {
                // Forzar crédito solicitado a no negativo
                $requestCredit = max(0.0, (float) $request->credit);
                $creditDifference = $requestCredit - $currentSaleCredit;

                if ($creditDifference != 0) {
                    $customer = Customer::where('customer', $request->customer)->first();
                    if ($customer) {
                        // Ajustar crédito del cliente basado en la diferencia
                        $newCustomerCredit = $customer->credit - $creditDifference;
                        Customer::where('id', $customer->id)->update([
                            'credit' => max(0, $newCustomerCredit),
                        ]);

                        // Asegurar que el crédito final no sea negativo
                        $finalCredit = max(0.0, $requestCredit);
                    }
                }
            }

            // 2. Calcular total y balance para determinar si está pagada
            $subtotal = (float) $request->subtotal;
            $credit = max(0.0, (float) $finalCredit);
            $taxPercentage = (float) $request->tax;

            // Calcular tax: (subtotal - crédito) * porcentaje / 100
            $subtotalAfterCredit = max(0.0, $subtotal - $credit);
            $calculatedTax = ($subtotalAfterCredit * $taxPercentage) / 100;

            // Total final: subtotal - (crédito - tax), si es negativo queda en 0
            $creditMinusTax = $credit - $calculatedTax;
            $calculatedTotal = max(0.0, $subtotal - $creditMinusTax);

            // Calculate balance_remaining based on calculated total and amount paid
            $amountPaidUpdate = max(0.0, (float) ($request->amount_paid ?? 0));
            $balance = round($calculatedTotal - $amountPaidUpdate, 2);
            if ($balance < 0) {
                $balance = 0;
            }

            $paid = ($balance == 0) ? 1 : 0;
            $itemStatus = ($paid == 1) ? Item::STATUS_SOLD : Item::STATUS_RESERVED;

            // 3. Actualizar ítems existentes
            foreach ($request->items as $item) {
                $sale_item = Item::find($item['id']);

                if ($paid == 1) {
                    // Mirror SaleController.php:126-128: capture the snapshot from
                    // the in-memory model BEFORE the update nulls the active fields.
                    // The boot hook's is_null($item->sold_storage_id) guard
                    // (Item.php:349) makes the explicit copy + hook coexist safely.
                    // Idempotency: do not overwrite an existing snapshot if the item
                    // was already sold once and is being re-edited.
                    $snapshot = [];
                    if (is_null($sale_item->sold_storage_id) && ! is_null($sale_item->storage_id)) {
                        $snapshot = [
                            'sold_storage_id' => $sale_item->storage_id,
                            'sold_position' => $sale_item->position,
                            'sold_storage_name' => $sale_item->storage?->name,
                        ];
                    }
                    $sale_item->update(array_merge([
                        'selling_price' => $item['selling_price'],
                        'profit' => $item['selling_price'] - $item['cost'],
                        'customer' => $request->customer,
                        'status' => $itemStatus,
                        'sold' => $request->date,
                        'position' => null,
                        'storage_id' => null,
                    ], $snapshot));
                } else {
                    // Reverting to unpaid — update fields, then handle position
                    $sale_item->selling_price = $item['selling_price'];
                    $sale_item->profit = $item['selling_price'] - $item['cost'];
                    $sale_item->customer = $request->customer;

                    if ($sale_item->getOriginal('status') === Item::STATUS_SOLD) {
                        // Was sold → now reverting: restore position if possible
                        $sale_item->revertToReserved();
                    } else {
                        // Was already reserved/available → keep position
                        $sale_item->sold = null;
                        $sale_item->status = $itemStatus;
                        $sale_item->save();
                    }
                }
            }

            // 4. Procesar nuevos ítems
            if (! empty($request->newItems)) {
                foreach ($request->newItems as $item) {
                    $newItemData = [
                        'date' => $request->date,
                        'model' => $item['model'],
                        'issues' => $item['issues'] ?? '',
                        'imei' => $item['imei'] ?? '',
                        'selling_price' => $item['selling_price'],
                        'customer' => $request->customer,
                        'sale_id' => $request->id,
                        'user_id' => $user->id,
                        'type' => $item['type'],
                        'status' => $itemStatus,
                    ];

                    if ($paid == 1) {
                        $newItemData['sold'] = $request->date;
                    } else {
                        $newItemData['sold'] = null;
                    }

                    // Auto-assign storage so the Item saving hook (Item.php:347-356)
                    // can snapshot a real sold_storage_* triplet when the item is SOLD.
                    // For unpaid items, the creating hook (Item.php:370-377) uses the
                    // storage_id to assign a position.
                    // NOTE: We set position explicitly here because the Item::saving hook
                    // (Item.php:322) fires BEFORE the Item::creating hook (Item.php:370) due
                    // to Eloquent's registration-order event semantics. The saving hook
                    // nulls storage_id before creating can use it to assign position. This
                    // workaround is duplicated from the store method (Change 1). A proper
                    // fix would reorder the hook registration; tracked as a follow-up.
                    $storageSlot = Storage::findFirstAvailablePosition();
                    if ($storageSlot !== null) {
                        $newItemData['storage_id'] = $storageSlot['storage_id'];
                        $newItemData['position'] = $storageSlot['position'];
                    } else {
                        Log::warning('No storage available for new sale item', [
                            'user_id' => $user->id,
                            'sale_id' => $request->id,
                        ]);
                    }

                    Item::create($newItemData);
                }
            }

            // 5. Actualizar la venta
            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $request->discount,
                'tax' => $taxPercentage,
                'flatTax' => round($calculatedTax, 2),
                'total' => round($calculatedTotal, 2),
                'amount_paid' => $request->amount_paid,
                'balance_remaining' => $balance,
                'payment_method' => $request->payment_method,
                'payment_account' => $request->payment_account,
                'notes' => $request->notes,
                'paid' => $paid,
                'date' => $request->date,
                'tax_id' => $request->tax_id,
                'credit' => max(0.0, (float) $finalCredit),
            ]);

            // Invalidate dashboard cache for the authenticated user
            $this->cacheService->invalidateForUser(Auth::id());

            return response()->json($request, 201);
        } catch (Exception $e) {
            Log::error('Error updating sale: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate a pdf receipt for the given Sale.
     */
    public function receipt(Sale $sale)
    {
        $user_data = User::where('id', $sale->user_id)->first();
        $store_id = $user_data->store_id;
        $user_role = $user_data->role;

        $customer = $sale->items[0]->customer;
        if (is_numeric($customer)) {
            $customer = Customer::whereId($sale->items[0]->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone')->first();
            if ($customer) {
                $customer = $customer;
            } else {
                $customer = $sale->items[0]->customer;
            }
        }

        $header = null;
        $footer = null;
        $logo = null;
        $sales = collect([$sale]);
        $returned_items = collect([]);

        if ($user_role == 'OWNER') {
            $header = $user_data->invoice_header;
            $footer = $user_data->invoice_footer;
        } else {
            $store = Store::where('id', $store_id)->first();
            if ($store) {
                $header = $store->header;
                $footer = $store->footer;
            }
        }

        // Logic for Logo Selection (User -> Company -> Store -> Default)
        $logo = null;

        // 1. User Personal Logo
        if (! is_null($user_data->invoice_logo) && StorageFacade::disk('local')->exists($user_data->invoice_logo)) {
            $logo = base64_encode(StorageFacade::disk('local')->get($user_data->invoice_logo));
        }

        // 2. Company Logo (Fallback)
        if (! $logo) {
            // Explicitly load company relationship to be sure
            $user_data->load('company');

            if ($user_data->company) {
                if ($user_data->company->logo) {
                    if (StorageFacade::disk('local')->exists($user_data->company->logo)) {
                        $logo = base64_encode(StorageFacade::disk('local')->get($user_data->company->logo));
                    }
                }
            }
        }

        // 3. Store Logo (Fallback for non-owners or if store has logo)
        $store = Store::where('id', $store_id)->first();
        if (! $logo && $store && ! is_null($store->logo) && $store->logo != '') {
            if (StorageFacade::disk('local')->exists($store->logo)) {
                $logo = base64_encode(StorageFacade::disk('local')->get($store->logo));
            } elseif (file_exists(storage_path('app/'.$store->logo))) {
                $logo = base64_encode(file_get_contents(storage_path('app/'.$store->logo)));
            } elseif (file_exists(storage_path().'/app/'.$store->logo)) {
                $logo = base64_encode(file_get_contents(storage_path().'/app/'.$store->logo));
            }
        }

        $pdf = Pdf::loadView('sale-receipt-invoice', compact('sales', 'customer', 'header', 'footer', 'logo', 'returned_items'))
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => 'true',
            ])
            ->setPaper('a4', 'portrait');

        $customer_name = $customer->customer;

        return $pdf->download("$customer_name invoice #$sale->id.pdf");
    }

    /**
     * Show report for Sale - Unified version with optional date range
     */
    public function showReport(Request $request): Response|JsonResponse
    {
        try {
            // Si se pasan fechas por request, usarlas; sino usar últimos 7 días
            $start = $request->start ? Carbon::parse($request->start)->startOfDay()->toDateTimeString() : Carbon::now()->subDays(7)->startOfDay()->toDateTimeString();
            $end = $request->end ? Carbon::parse($request->end)->endOfDay()->toDateTimeString() : Carbon::now()->endOfDay()->toDateTimeString();
            $user = Auth::user();

            $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
            $fields = CustomField::where('user_id', $user->id)->get();

            $sales = Sale::select([
                'sales.id',
                'sales.tax',
                'sales.created_at',
            ])
                ->join('items', 'sales.id', '=', 'items.sale_id')
                ->where(function ($query) use ($start, $end) {
                    $query->where(function ($q) use ($start, $end) {
                        $q->whereBetween('items.sold', [$start, $end]);
                    })->orWhere(function ($q) use ($start, $end) {
                        $q->whereBetween('sales.created_at', [$start, $end]);
                    });
                })
                ->whereNotNull('items.sale_id')
                ->whereIn('items.type', ['device', 'accessory'])
                ->distinct()
                ->with([
                    'items' => function ($query) {
                        $query->select([
                            'id', 'sale_id', 'customer', 'battery', 'cost',
                            'selling_price', 'sold', 'partially_sold_at', 'vendor_id', 'model',
                            'manufacturer', 'colour', 'grade', 'issues', 'imei',
                            'date', 'type', 'sold_position', 'sold_storage_name',
                            'sold_storage_id', 'custom_values',
                            'storage_id', 'position',
                        ]);
                    },
                    'items.vendor:id,vendor',
                    'items.storage:id,name,limit',
                ])
                ->get();

            $customerIds = $sales->flatMap(function ($sale) {
                return $sale->items->pluck('customer');
            })
                ->filter(function ($customer) {
                    return is_numeric($customer);
                })
                ->unique()
                ->values();

            $customers = Customer::whereIn('id', $customerIds)
                ->select('id', 'customer')
                ->get()
                ->keyBy('id');

            $formatted_items = [];

            foreach ($sales as $sale) {
                $tax = intval($sale->tax) / 100;

                foreach ($sale->items as $item) {
                    $battery = is_numeric($item->battery) ? "{$item->battery} %" : $item->battery;

                    if (is_numeric($item->customer) && isset($customers[$item->customer])) {
                        $item->customer = $customers[$item->customer]->customer;
                    }

                    $cost = (float) $item->cost;
                    $selling_price = (float) $item->selling_price;
                    $total = $selling_price + ($selling_price * $tax);
                    $profit = $total - $cost;

                    // Procesar custom values para mantener compatibilidad
                    $customValues = json_decode($item->custom_values ?: '[]', true);
                    $customFields = [];
                    foreach ($customValues as $field) {
                        $customFields["{$field['slug']}_{$field['id']}"] = $field['value'];
                    }

                    $formatted_items[] = array_merge([
                        'id' => $item->id,
                        'sale_id' => $item->sale_id,
                        'customer' => $item->customer,
                        'model' => $item->model,
                        'manufacturer' => $item->manufacturer,
                        'colour' => $item->colour,
                        'grade' => $item->grade,
                        'issues' => $item->issues,
                        'imei' => $item->imei,
                        'battery' => $battery,
                        'sold' => $item->sold
                            ? Carbon::parse($item->sold)->format('Y-m-d')
                            : ($item->partially_sold_at ? Carbon::parse($item->partially_sold_at)->format('Y-m-d') : null),
                        'date' => Carbon::parse($item->date)->format('Y-m-d'),
                        'type' => $item->type,
                        'cost' => '$ '.number_format($cost, 2),
                        'subtotal' => '$ '.number_format($selling_price, 2),
                        'total' => '$ '.number_format($total, 2),
                        'profit' => '$ '.number_format($profit, 2),
                        'vendor' => $item->vendor,
                        'sold_position' => $item->sold_position,
                        'sold_storage_name' => $item->sold_storage_name,
                        'sold_storage_id' => $item->sold_storage_id,
                        'storage_id' => $item->storage_id,
                        'position' => $item->position,
                        'storage' => $item->storage ? [
                            'id' => $item->storage->id,
                            'name' => $item->storage->name,
                            'limit' => $item->storage->limit,
                        ] : null,
                        'supplier' => $item->vendor?->vendor ?? null,
                    ], $customFields);
                }
            }

            if (config('app.debug')) {
                Log::info('items: ', $formatted_items);
                Log::info('items count: '.count($formatted_items));
            }

            // Si es vista inicial, devolver Inertia con todos los datos
            return Inertia::render('Inventory/Sold', [
                'tabs' => $tabs,
                'fields' => $fields,
                'items' => $formatted_items,
            ]);

        } catch (Exception $e) {
            Log::error('Error in showReport: '.$e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return Inertia::render('Error', ['message' => $e->getMessage()]);
        }
    }

    public function soldItems($id): JsonResponse
    {

        $soldItems = Item::where('sale_id', $id)->get();
        $soldItems = $soldItems->map(function ($item) {
            $item['selected'] = false; // You can set the value to whatever you need

            return $item;
        });

        return response()->json($soldItems);
    }
}
