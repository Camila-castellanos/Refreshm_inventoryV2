<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Item;
use App\Http\Requests\VendorForm;
use App\Models\Tab;
use App\Models\TabItem;
use App\Models\TabHistory;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Sale;
use App\Models\Bill;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Excel;
use Illuminate\Support\Facades\Auth;
use App\Imports\ItemsImport;
use App\Exports\ItemDemoExport;
use Illuminate\Support\Facades\Mail;
use Response;
use Exception;

class VendorController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendors = Vendor::all();
        foreach ($vendors as $vendor) {
            $items = Item::where("vendor_id", $vendor->id)
            ->whereBetween('sold', [now()->subYear()->startOfDay(), now()->endOfDay()])
                ->get();

            $total = [];
            $profit = [];
            $balance = [];
            $total_spend = [];

            foreach ($items as $item) {
                $sale = Sale::whereId($item->sale_id)->first();

                $tax = 0;
                if ($sale) {
                    $tax = $sale->tax;
                }

                $value = $item->selling_price + ($item->selling_price * $tax / 100);
                $total[] = $value;
                $profit[] = $value - $item->cost;
            }

            $bills = Bill::where("vendor_id", $vendor->id)->whereBetween('date', [now()->subYears(4)->startOfDay(), now()->endOfDay()])->get();
            foreach ($bills as $bill) {
                $balance[] = $bill->balance_remaining;
                $total_spend[] = $bill->total;
            }

            $total = array_sum($total);
            $profit = array_sum($profit);
            if ($profit != 0 && $total != 0) {
                $margin = ($profit / $total) * 100;
            } else {
                $margin = 0;
            }

            $total_spend = array_sum($total_spend);
            $balance = array_sum($balance);
            $vendor->revenue = $total < 0 ? 0 : $total;
            $vendor->profit = $profit < 0 ? 0 : $profit;
            $vendor->margin = $margin < 0 ? 0 : $margin;
            $vendor->balance = $balance < 0 ? 0 : $balance;
            $vendor->total_spend = $total_spend < 0 ? 0 : $total_spend;
        }
        return Inertia::render('Vendors/Vendors', compact("vendors"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Vendors/CreateEdit');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  VendorForm  $request
     * @return \Illuminate\Http\Response
     */
    public function store(VendorForm $request)
    {
        try {
            $form = $request->validated();
            $form['user_id'] = Auth::user()->id;
            $vendor = Vendor::create($form);
            return response()->json($vendor, 201);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Vendor  $vendor
     * @return \Illuminate\Http\Response
     */
    public function edit(Vendor $vendor)
    {
        return Inertia::render('Vendors/CreateEdit', [
            "vendorEdit" => $vendor
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request\VendorForm  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(VendorForm $request, Vendor $vendor)
    {
        $form = $request->validated();

        if ($vendor->update($form)) {
            return response()->json($vendor, 200);
        } else {
            return response()->json('', 500);
        }
    }

    public function vendorList(Request $request)
    {
        $vendor = $request->search;
        $vendors = Vendor::where(function ($query) use ($vendor) {
            $query->where('vendor', 'LIKE', '%' . $vendor . '%');
            $query->orWhere('first_name', 'LIKE', '%' . $vendor . '%');
            $query->orWhere('last_name', 'LIKE', '%' . $vendor . '%');
        })->get();
        foreach ($vendors as $vendor) {
            $vendor->vendor_name = $vendor->first_name . " " . $vendor->last_name;
        }
        return response()->json($vendors, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Vendor $vendor)
    {
        if ($vendor->delete()) {
            return response()->json('OK', 200);
        } else {
            return response()->json('', 500);
        }
    }

    /**
     * Get the specified resource from storage by date.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function datewise(Request $request)
    {
        $vendors = Vendor::all();
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        foreach ($vendors as $vendor) {
            $items = Item::where("vendor_id", $vendor->id)->where('sold', '>=', $start)
                ->where('sold', '<=', $end)->get();
            // $sale_pks = $items->map(function ($item) {
            //     return $item->sale_id;
            // })->toArray();

            $total = [];
            $profit = [];
            $balance = [];
            $total_spend = [];

            foreach ($items as $item) {
                $sale = Sale::whereId($item->sale_id)->first();

                $tax = 0;
                if ($sale) {
                    $tax = $sale->tax;
                }

                $value = $item->selling_price + ($item->selling_price * $tax / 100);
                $total[] = $value;
                $profit[] = $value - $item->cost;
            }

            $bills = Bill::where("vendor_id", $vendor->id)->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)->get();
            foreach ($bills as $bill) {
                $balance[] = $bill->balance_remaining;
                $total_spend[] = $bill->total;
            }

            $total = array_sum($total);
            $profit = array_sum($profit);
            if ($profit != 0) {
                $margin = ($profit / $total) * 100;
            } else {
                $margin = 0;
            }

            $total_spend = array_sum($total_spend);
            $balance = array_sum($balance);
            $vendor->revenue = $total < 0 ? 0 : $total;
            $vendor->profit = $profit < 0 ? 0 : $profit;
            $vendor->margin = $margin < 0 ? 0 : $margin;
            $vendor->balance = $balance < 0 ? 0 : $balance;
            $vendor->total_spend = $total_spend < 0 ? 0 : $total_spend;
        }
        return response()->json($vendors, 200);
    }
}
