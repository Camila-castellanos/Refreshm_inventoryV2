<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorForm;
use App\Models\Vendor;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $cacheKey = "vendors_index_user_{$user->id}";

        $vendors = Cache::remember($cacheKey, 1200, function () {
            $startDate = now()->subYear()->startOfDay();
            $endDate = now()->endOfDay();

            return Vendor::select([
                'id',
                'vendor',
                'first_name',
                'last_name',
                'email',
                'phone',
                'phone_optional',
                'website',
                'notes',
                'currency',
                'address',
                'address_optional',
                'address_country',
                'address_state',
                'address_city',
                'address_postal',
                'created_at',
                'updated_at',
            ])
                ->selectSub(function ($query) use ($startDate, $endDate) {
                    $query->from('items')
                        ->join('sales', 'items.sale_id', '=', 'sales.id')
                        ->whereColumn('items.vendor_id', 'vendors.id')
                        ->whereBetween('items.sold', [$startDate, $endDate])
                        ->selectRaw('COALESCE(SUM(items.selling_price + (items.selling_price * sales.tax / 100)), 0)');
                }, 'revenue')
                ->selectSub(function ($query) use ($startDate, $endDate) {
                    $query->from('items')
                        ->join('sales', 'items.sale_id', '=', 'sales.id')
                        ->whereColumn('items.vendor_id', 'vendors.id')
                        ->whereBetween('items.sold', [$startDate, $endDate])
                        ->selectRaw('COALESCE(SUM((items.selling_price + (items.selling_price * sales.tax / 100)) - items.cost), 0)');
                }, 'profit')
                ->selectSub(function ($query) {
                    $query->from('items')
                        ->whereColumn('items.vendor_id', 'vendors.id')
                        ->selectRaw('count(*)');
                }, 'devices_count')
                ->selectSub(function ($query) {
                    $startDate = now()->subYears(4)->startOfDay();
                    $endDate = now()->endOfDay();
                    $query->from('bills')
                        ->whereColumn('bills.vendor_id', 'vendors.id')
                        ->whereBetween('bills.date', [$startDate, $endDate])
                        ->selectRaw('COALESCE(SUM(bills.balance_remaining), 0)');
                }, 'balance')
                ->selectSub(function ($query) {
                    $startDate = now()->subYears(4)->startOfDay();
                    $endDate = now()->endOfDay();
                    $query->from('bills')
                        ->whereColumn('bills.vendor_id', 'vendors.id')
                        ->whereBetween('bills.date', [$startDate, $endDate])
                        ->selectRaw('COALESCE(SUM(bills.total), 0)');
                }, 'total_spend')
                ->get()
                ->map(function ($vendor) {
                    // Calcular margen
                    $revenue = (float) $vendor->revenue;
                    $profit = (float) $vendor->profit;

                    if ($profit != 0 && $revenue != 0) {
                        $margin = ($profit / $revenue) * 100;
                    } else {
                        $margin = 0;
                    }

                    // Asegurar que no hay valores negativos
                    $vendor->revenue = $revenue < 0 ? 0 : $revenue;
                    $vendor->profit = $profit < 0 ? 0 : $profit;
                    $vendor->margin = $margin < 0 ? 0 : $margin;
                    $vendor->balance = $vendor->balance < 0 ? 0 : $vendor->balance;
                    $vendor->total_spend = $vendor->total_spend < 0 ? 0 : $vendor->total_spend;
                    $vendor->devices_count = (int) $vendor->devices_count;

                    return $vendor;
                });
        });

        return Inertia::render('Vendors/Vendors', compact('vendors'));
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
     * @return \Illuminate\Http\Response
     */
    public function store(VendorForm $request)
    {
        try {
            $form = $request->validated();
            $form['user_id'] = Auth::user()->id;
            $vendor = Vendor::create($form);

            // Invalidar cache de vendors
            $user = Auth::user();
            Cache::forget("vendors_index_user_{$user->id}");

            return response()->json($vendor, 201);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Vendor $vendor)
    {
        return Inertia::render('Vendors/CreateEdit', [
            'vendorEdit' => $vendor,
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
            // Invalidar cache de vendors
            $user = Auth::user();
            Cache::forget("vendors_index_user_{$user->id}");

            return response()->json($vendor, 200);
        } else {
            return response()->json('', 500);
        }
    }

    public function vendorList(Request $request)
    {
        $vendor = $request->search;
        $vendors = Vendor::where(function ($query) use ($vendor) {
            $query->where('vendor', 'LIKE', '%'.$vendor.'%');
            $query->orWhere('first_name', 'LIKE', '%'.$vendor.'%');
            $query->orWhere('last_name', 'LIKE', '%'.$vendor.'%');
        })->get();
        foreach ($vendors as $vendor) {
            $vendor->vendor_name = $vendor->first_name.' '.$vendor->last_name;
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
            // Invalidar cache de vendors
            $user = Auth::user();
            Cache::forget("vendors_index_user_{$user->id}");

            return response()->json('OK', 200);
        } else {
            return response()->json('', 500);
        }
    }

    /**
     * Get the specified resource from storage by date.
     *
     * @return \Illuminate\Http\Response
     */
    public function datewise(Request $request)
    {
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $vendors = Vendor::select([
            'id',
            'vendor',
            'first_name',
            'last_name',
            'email',
            'phone',
            'phone_optional',
            'website',
            'notes',
            'currency',
            'address',
            'address_optional',
            'address_country',
            'address_state',
            'address_city',
            'address_postal',
            'created_at',
            'updated_at',
        ])
            ->selectSub(function ($query) use ($start, $end) {
                $query->from('items')
                    ->join('sales', 'items.sale_id', '=', 'sales.id')
                    ->whereColumn('items.vendor_id', 'vendors.id')
                    ->where('items.sold', '>=', $start)
                    ->where('items.sold', '<=', $end)
                    ->selectRaw('COALESCE(SUM(items.selling_price + (items.selling_price * sales.tax / 100)), 0)');
            }, 'revenue')
            ->selectSub(function ($query) use ($start, $end) {
                $query->from('items')
                    ->join('sales', 'items.sale_id', '=', 'sales.id')
                    ->whereColumn('items.vendor_id', 'vendors.id')
                    ->where('items.sold', '>=', $start)
                    ->where('items.sold', '<=', $end)
                    ->selectRaw('COALESCE(SUM((items.selling_price + (items.selling_price * sales.tax / 100)) - items.cost), 0)');
            }, 'profit')
            ->selectSub(function ($query) use ($start, $end) {
                $query->from('items')
                    ->whereColumn('items.vendor_id', 'vendors.id')
                    ->where('items.date', '>=', $start)
                    ->where('items.date', '<=', $end)
                    ->selectRaw('count(*)');
            }, 'devices_count')
            ->selectSub(function ($query) use ($start, $end) {
                $query->from('bills')
                    ->whereColumn('bills.vendor_id', 'vendors.id')
                    ->whereDate('bills.date', '>=', $start)
                    ->whereDate('bills.date', '<=', $end)
                    ->selectRaw('COALESCE(SUM(bills.balance_remaining), 0)');
            }, 'balance')
            ->selectSub(function ($query) use ($start, $end) {
                $query->from('bills')
                    ->whereColumn('bills.vendor_id', 'vendors.id')
                    ->whereDate('bills.date', '>=', $start)
                    ->whereDate('bills.date', '<=', $end)
                    ->selectRaw('COALESCE(SUM(bills.total), 0)');
            }, 'total_spend')
            ->get()
            ->map(function ($vendor) {
                // Calcular margen
                $revenue = (float) $vendor->revenue;
                $profit = (float) $vendor->profit;

                if ($profit != 0 && $revenue != 0) {
                    $margin = ($profit / $revenue) * 100;
                } else {
                    $margin = 0;
                }

                // Asegurar que no hay valores negativos
                $vendor->revenue = $revenue < 0 ? 0 : $revenue;
                $vendor->profit = $profit < 0 ? 0 : $profit;
                $vendor->margin = $margin < 0 ? 0 : $margin;
                $vendor->balance = $vendor->balance < 0 ? 0 : $vendor->balance;
                $vendor->total_spend = $vendor->total_spend < 0 ? 0 : $vendor->total_spend;
                $vendor->devices_count = (int) $vendor->devices_count;

                return $vendor;
            });

        return response()->json($vendors, 200);
    }
}
