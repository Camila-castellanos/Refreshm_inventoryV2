<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleForm;
use App\Http\Requests\SaleFormEdit;
use App\Models\Payment;
use App\Models\CustomField;
use App\Models\CashOnHand;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ReturnItems;
use App\Models\Tab;
use App\Models\TabItem;
use App\Models\Sale;
use App\Models\Storage;
use App\Models\Store;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SaleForm $request)
    {
        $form = $request->validated();
        $form["user_id"] = Auth::user()->id;
        $form["balance_remaining"] = $request->balance_remaining;
        // Convertir payment_date (Y-m-d) a datetime con hora actual y asignar al form
        $paymentDateTime = Carbon::createFromFormat('Y-m-d', $request->payment_date)
            ->setTimeFromTimeString(Carbon::now()->toTimeString());
        $form["date"] = $paymentDateTime->format('Y-m-d H:i:s');
        // Crear la venta con datetime completo
        $sale = Sale::create($form);

        foreach ($form["items"] as $sale_item) {
            $sale_item["sale_id"] = $sale->id;
            $sale_item["type"] = $sale_item["type"];
            $sale_item['sold_position'] = $sale_item['position'];
            $sale_item['sold_storage_id'] = $sale_item['storage_id'];
            $sale_item['sold_storage_name'] = Storage::find($sale_item['storage_id'])?->name;
            $sale_item['sold'] = Carbon::now();
            unset($sale_item["selected"]);
            $item = Item::find($sale_item["id"]);

            if ($request->paid == 1) {
                Payment::insert([
                    'sale_id' => $sale->id,
                    'amount_paid' => $form["total"],
                    'balance_remaining' => $form["balance_remaining"],
                    'payment_method' => $form["payment_method"],
                    'payment_account' => $form["payment_account"],
                    'payment_date' => $paymentDateTime->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $item->update($sale_item);

            if ($item) {
                $item->update([
                    'position' => null,
                    'storage_id' => null,

                ]);
            }

            TabItem::where('item_id', $sale_item["id"])->delete();
        }

        if ($request->paid == 1) {
            if ($request->payment_account == "Cash on Hand") {
                $old_cash = CashOnHand::where('user_id', $form["user_id"])->value("balance");
                CashOnHand::where('user_id', $form["user_id"])->update([
                    'balance' => $old_cash + $form["total"],
                ]);
            }
        }

        foreach ($request->newItems as $new_item) {
            $total = $new_item["selling_price"] + (($form["tax"] / 100) * $new_item["selling_price"]);

            $item = Item::create([
                'date' => $request->payment_date,
                'type' => $new_item['type'],
                'model' => $new_item['model'],
                'issues' => $new_item['issues'],
                'imei' => $new_item['imei'],
                'selling_price' => $new_item['selling_price'],
                'sale_id' => $sale->id,
                'customer' => $new_item['customer'],
                'discount' => $request->discount,
                'tax' => $request->tax,
                'sold' => Carbon::now(),
                'user_id' => $form["user_id"],
                'profit' => $new_item['profit'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($request->paid == 1) {
                Payment::insert([
                    'sale_id' => $sale->id,
                    'amount_paid' => $form["total"],
                    'balance_remaining' => $form["balance_remaining"],
                    'payment_method' => $form["payment_method"],
                    'payment_account' => $form["payment_account"],
                    'payment_date' => $paymentDateTime->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($request->payment_account == "Cash on Hand") {
                    $old_cash = CashOnHand::where('user_id', $form["user_id"])->value("balance");
                    CashOnHand::where('user_id', $form["user_id"])->update([
                        'balance' => $old_cash + $form["total"],
                    ]);
                }
            }
        }

        $receiptUrl = route("sales.receipt", $sale);
        return response()->json($receiptUrl, 201);
    }

    /**
     *  Returns JSON listing the updates on sale
     *  date range ($start - $end).
     *
     *  @param SaleForm $request
     *  @return JsonResponse
     */
    public function update(SaleFormEdit $request)
    {
        try {
            $request->validated();
            $sale = Sale::find($request->id);
            $user = Auth::user();
            $balance = $sale->balance_remaining;
            $total = 0;

            foreach ($request->items as $item) {
                $sale_item = Item::find($item['id']);
                $sale_item->update([
                    'selling_price' => $item['selling_price'],
                    'profit' => $item['selling_price'] - $item['cost'],
                    'customer' => $request->customer,
                    'sold' => $request->date,
                ]);

                $total += $item['selling_price'];
            }

            if (!empty($request->newItems)) {
                foreach ($request->newItems as $item) {
                    Item::insert([
                        'date' => $request->date,
                        'model' => $item['model'],
                        'issues' => $item['issues'] ?? '',
                        'imei' => $item['imei'] ?? '',
                        'selling_price' => $item['selling_price'],
                        'sold' => $request->date,
                        'customer' => $request->customer,
                        'sale_id' => $request->id,
                        'user_id' => $user->id,
                        'type' => $item['type'],
                    ]);

                    $total += $item['selling_price'];
                }
            }

            $finalCredit = $request->credit + $sale->credit;
            $customerAdded = $request->credit - $sale->credit;
            if ($request->credit != null || $request->credit > 0) {
                $customer = Customer::where('customer', $request->customer)->first();
                $credit = $customer->credit - $customerAdded;
                Customer::where('id', $customer->id)->update([
                    'credit' => $credit + $request->removed_credit,
                ]);
                Log::info("credit update with the following data", [
                    'request_credit' => $request->credit,
                    'sale_credit' => $sale->credit,
                    'customerAdded' => $customerAdded,
                    'customer' => $customer,
                    'removed_credit' => $request->removed_credit,
                    'credit' => $credit,
                ]);
            }

            $finalCredit = $request->credit - $request->removed_credit;

            $paid = 0;
            $balance = $request->balance_remaining;

            if ($balance < 0) {
                $balance = 0;
                $paid = 1;
            } else if ($balance == 0) {
                $paid = 1;
            }

            $sale->update([
                'subtotal' => $request->subtotal,
                'discount' => $request->discount,
                'tax' => $request->tax,
                'flatTax' => $request->flatTax,
                'total' => $request->total,
                'amount_paid' => $request->amount_paid,
                'balance_remaining' => $balance,
                'payment_method' => $request->payment_method,
                'payment_account' => $request->payment_account,
                'notes' => $request->notes,
                'paid' => $paid,
                'date' => $request->date,
                'tax_id' => $request->tax_id,
                'credit' => $finalCredit,
            ]);

            return response()->json($request, 201);
        } catch (Exception $e) {
            dd($e);
            return Inertia::render('Error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Generate a pdf receipt for the given Sale.
     * @param Sale $sale
     * @return
     */
    public function receipt(Sale $sale)
    {
        $user_data = User::where('id', $sale->user_id)->first();
        $store_id = $user_data->store_id;
        $user_role = $user_data->role;

        $customer = $sale->items[0]->customer;
        if (is_numeric($customer)) {
            $customer = Customer::whereId($sale->items[0]->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone')->first();
            if ($customer)
                $customer = $customer;
            else
                $customer = $sale->items[0]->customer;
        }

        $header = null;
        $footer = null;
        $logo = null;
        $sales = collect([$sale]);
        $returned_items = collect([]);
        if ($user_role == "OWNER") {
            $header = $user_data->invoice_header;
            $footer = $user_data->invoice_footer;
            if (!is_null($user_data->invoice_logo) && file_exists(storage_path("app/" . $user_data->invoice_logo))) {
                $logo = base64_encode(file_get_contents(storage_path("app/" . $user_data->invoice_logo)));
            } else {
                $logo = base64_encode(file_get_contents(public_path("img/_REFRESHMOBILE.png")));
            }
        } else {

            $store = Store::where('id', $store_id)->first();
            if ($store) {
                $header = $store->header;
                $footer = $store->footer;
                if (!is_null($store->logo)) {
                    $logo = base64_encode(file_get_contents(storage_path() . "/app/" . $store->logo));
                } else {
                    $logo = base64_encode(file_get_contents(public_path() . "/img/_REFRESHMOBILE.png"));
                }
            }
        }

        $pdf = Pdf::loadView("sale-receipt-invoice", compact("sales", "customer", "header", "footer", "logo", "returned_items"))
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
            $start = $request->start ? Carbon::parse($request->start)->startOfDay() : Carbon::now()->subDays(7)->startOfDay();
            $end = $request->end ? Carbon::parse($request->end)->endOfDay() : Carbon::now()->endOfDay();
            $user = Auth::user();

            
            
                $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
                $fields = CustomField::where('user_id', $user->id)->get();

            $sales = Sale::select([
                    'sales.id',
                    'sales.tax',
                    'sales.created_at'
                ])
                ->join('items', 'sales.id', '=', 'items.sale_id')
                ->where('sales.user_id', $user->id)
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
                            'selling_price', 'sold', 'vendor_id', 'model',
                            'manufacturer', 'colour', 'grade', 'issues', 'imei',
                            'date', 'type', 'sold_position', 'sold_storage_name', 
                            'sold_storage_id', 'custom_values'
                        ]);
                    },
                    'items.vendor:id,vendor'
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
                        'sold' => Carbon::parse($item->sold)->format('Y-m-d'),
                        'date' => Carbon::parse($item->date)->format('Y-m-d'),
                        'type' => $item->type,
                        'cost' => '$ ' . number_format($cost, 2),
                        'subtotal' => '$ ' . number_format($selling_price, 2),
                        'total' => '$ ' . number_format($total, 2),
                        'profit' => '$ ' . number_format($profit, 2),
                        'vendor' => $item->vendor,
                        'sold_position' => $item->sold_position,
                        'sold_storage_name' => $item->sold_storage_name,
                        'sold_storage_id' => $item->sold_storage_id,
                        'supplier' => $item->vendor?->vendor ?? null,
                    ], $customFields);
                }
            }
            Log::info("items: ", $formatted_items);
            Log::info("items count: " . count($formatted_items));
            // Si es vista inicial, devolver Inertia con todos los datos
            return Inertia::render("Inventory/Sold", [
                'tabs' => $tabs,
                'fields' => $fields,
                'items' => $formatted_items,
            ]);
            
        } catch (Exception $e) {
            Log::error('Error in showReport: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return Inertia::render('Error', ['message' => $e->getMessage()]);
        }
    }

    
    public function soldItems($id): JsonResponse
    {

        $soldItems = Item::where("sale_id", $id)->get();
        $soldItems = $soldItems->map(function ($item) {
            $item['selected'] = false; // You can set the value to whatever you need
            return $item;
        });

        return response()->json($soldItems);
    }
}
