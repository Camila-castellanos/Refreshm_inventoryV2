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
        $form["date"] = $request->payment_date;

        $sale = Sale::create($form);

        log::info($form);

        foreach ($form["items"] as $sale_item) {
            $sale_item["sale_id"] = $sale->id;
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
                    'payment_date' => $request->payment_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            Log::info($sale_item);
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
                    'payment_date' => $request->payment_date,
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
                        'issues' => $item['issues'],
                        'imei' => $item['imei'],
                        'selling_price' => $item['selling_price'],
                        'sold' => $request->date,
                        'customer' => $request->customer,
                        'sale_id' => $request->id,
                        'user_id' => $user->id,
                    ]);

                    $total += $item['selling_price'];
                }
            }

            $finalCredit = $request->credit + $sale->credit;
            $customerAdded = $request->credit - $sale->credit;
            if ($request->credit != null || $request->credit > 0) {
                $customer = Customer::where('customer', $request->customer)->first();
                $credit = $customer->credit - $customerAdded;
                Customer::where('id', $request->customer)->update([
                    'credit' => $credit + $request->removed_credit,
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
     * Show report for Sale
     */
    public function showReport(): Response
    {
        try{
            $user = Auth::user();
    
            $tabs = Tab::where('user_id', $user->id)->orderBy('order', 'asc')->get();
            $fields = CustomField::where('user_id', $user->id)->get();
            $items = Item::whereNotNull('sold')
            ->whereNotNull('sale_id')
            ->where('sold', '>=', now()->subDays(7))
            ->with(['vendor:id,vendor', 'sale'])
            ->get();
            $context = [
                'tabs' => $tabs,
                'fields' => $fields,
                'items' => $items,
            ];
            return Inertia::render("Inventory/Sold", $context);
        }
        catch (Exception $e) {
            Log::error($e);
            return Inertia::render('Error', ['message' => $e->getMessage()]);
        }
    }

    /**
     *  Returns JSON listing all Item sold between a given
     *  date range ($start - $end).
     *
     *  @param Request $request
     *  @return JsonResponse
     */
    public function generateReport(Request $request): JsonResponse
    {
        $start = $request->start;
        $end = strtotime("1 day", strtotime($request->end));
        $user = Auth::user();

        $items = Item::where([
            ["sold", ">=", $start],
            ["sold", "<=", date("Y-m-d", $end)],
            ["user_id", $user->id]
        ])
            ->whereNotNull("sale_id")
            // ->whereHas('sale', function($query){
            //     $query->where('paid', 1);
            // })
            ->select("sale_id")
            ->distinct()
            ->with(['vendor:id,vendor'])
            ->get();

        $sale_pks = $items->map(function ($item) {
            return $item->sale_id;
        })->toArray();

        $sales = Sale::whereIn("id", $sale_pks);
        if (Auth::user()->role != "OWNER") {
            $store = Auth::user()->store;
            if (!$store) {
                return response()->json(['message' => 'No Store Available'], 500);
            }

            $ids = $store->users->pluck("id");

            switch (Auth::user()->role) {
                case "ADMIN":
                    $store = Auth::user()->store;
                    $ids = $store->users->pluck("id");
                    $sales->whereIn("user_id", $ids->toArray());
                    break;
                case "USER":
                    $sales->whereIn("user_id", [Auth::id()]);
                    break;
                default:
                    abort(403, "Unauthorized.");
                    break;
            }
        }

        $sales = $sales->get();

        $response = [];
        foreach ($sales as $sale) {
            $tax = intval($sale->tax) / 100;
            foreach ($sale->items as $item) {

                $battery = $item->battery;
                if (is_numeric($item->battery)) {
                    $battery = "$battery %";
                }

                if (is_numeric($item->customer)) {
                    $customers = Customer::whereId($item->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone')->first();
                    if ($customers)
                        $item->customer = $customers->customer;
                }

                $cost = number_format(floatval($item->cost), 2);
                $subtotal = number_format($item->selling_price, 2);
                $total = $item->selling_price + $item->selling_price * $tax;
                $profit = (float) $total - (float) $item->cost;

                $sold = new DateTime($item->sold);

                $total = number_format($total, 2);
                $profit = number_format($profit, 2);
                $item["sold"] = $sold->format("Y-m-d");
                $item["cost"] = "$ $cost";
                $item["subtotal"] = "$ $subtotal";
                $item["total"] = "$ $total";
                $item["profit"] = "$ $profit";
                $item["battery"] = $battery;
                $item["supplier"] = $item->vendor?->vendor ?? null;

                $response[] = $item;
            }
        }

        return response()->json($response);
    }

    /**
     *  Returns JSON listing all Item sold between a given
     *  date range ($start - $end).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function soldItems($id): JsonResponse
    {

        $soldItems = Item::where("sale_id", $id)->get();
        $soldItems = $soldItems->map(function ($item) {
            $item['selected'] = false; // You can set the value to whatever you need
            return $item;
        });

        return response()->json($soldItems);
    }

    public function getSolditems(Request $request): JsonResponse
    {
        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        try{
            $query = Item::whereNotNull('sold')
                ->whereNotNull('sale_id')
                ->with(['vendor:id,vendor', 'sale']);

            if ($request->has('start') && $request->has('end')) {
                $query->whereBetween('sold', [$start, $end]);
            } else {
                $query->where('sold', '>=', now()->subDays(7));
            }

            $soldItems = $query->get();

            return response()->json($soldItems, 200);
        }

        catch (Exception $e) {
            return response()->json(['message' => "{$e}"], 500);
        }
    }
}
