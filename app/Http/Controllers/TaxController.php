<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\Bill;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Percentage;
use Carbon\Carbon;

class TaxController extends Controller
{
  public function show()
  {
    try {
      $user = Auth::user();
      $taxes = Tax::where('user_id', $user->id)->get();
      $currentDate = Carbon::now();
      // Default range: from start of current year up to now (year-to-date)
      $firstDayOfYear = Carbon::now()->startOfYear();
      $response = [];

      // Align non-taxed sales calculation with Dashboard: sum items.selling_price
      // where sales.tax IS NULL OR sales.tax = 0, filtered by items.sold in the chosen range
      $start = Carbon::now()->startOfYear()->startOfDay()->toDateTimeString();
      // default end is now (end of today)
      $end = Carbon::now()->endOfDay()->toDateTimeString();

      $nonTaxedSales = DB::table('items')
        ->leftJoin('sales', 'items.sale_id', '=', 'sales.id')
        ->where('items.user_id', $user->id)
        ->whereBetween('items.sold', [$start, $end])
        ->where(function($q) {
          $q->whereNull('sales.tax')->orWhere('sales.tax', 0);
        })
        ->selectRaw('COALESCE(SUM(items.selling_price), 0) as s')
        ->value('s');

      // Also compute totals for records where tax_id IS NULL (aggregate across percentages)
      $saleFlatNullTotal = (float) Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('flatTax');

      $saleSubtotalNullTotal = (float) Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('subtotal');

      $billFlatNullTotal = (float) Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('flat_tax');

      $billSubtotalNullTotal = (float) Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('subtotal');

      // Also compute totals for records where tax_id IS NULL (aggregate across percentages)
      $saleFlatNullTotal = (float) Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('flatTax');

      $saleSubtotalNullTotal = (float) Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('subtotal');

      $billFlatNullTotal = (float) Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('flat_tax');

      $billSubtotalNullTotal = (float) Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->sum('subtotal');


      // Build percentage counts across all taxes to decide attribution of
      // records with tax_id = NULL. We only attribute percentage-only rows
      // when the percentage is unique among the user's taxes (safer: avoid
      // double-counting when multiple taxes share the same percentage).
      $percentageCounts = [];
      foreach ($taxes as $tax) {
        $k = (string) ((float) $tax->percentage);
        $percentageCounts[$k] = ($percentageCounts[$k] ?? 0) + 1;
      }

      // Pre-aggregate percent-based sums for sales and bills (those with tax_id NULL)
      // restricted to the selected date range
      $saleFlatByPercentMap = Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->select('tax', DB::raw('SUM(flatTax) as sum_flat'))
        ->groupBy('tax')
        ->pluck('sum_flat', 'tax')
        ->toArray();

      $saleSubtotalByPercentMap = Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->select('tax', DB::raw('SUM(subtotal) as sum_sub'))
        ->groupBy('tax')
        ->pluck('sum_sub', 'tax')
        ->toArray();

      $billFlatByPercentMap = Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->select('tax', DB::raw('SUM(flat_tax) as sum_flat'))
        ->groupBy('tax')
        ->pluck('sum_flat', 'tax')
        ->toArray();

      $billSubtotalByPercentMap = Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->whereBetween('date', [$start, $end])
        ->select('tax', DB::raw('SUM(subtotal) as sum_sub'))
        ->groupBy('tax')
        ->pluck('sum_sub', 'tax')
        ->toArray();

      // For each tax, compute sums using tax_id first; if percentage is unique
      // then add percent-based sums from NULL-tax_id rows.
      foreach ($taxes as $tax) {
        $taxId = $tax->id;
        $percentage = (float) $tax->percentage;
        $pkey = (string) $percentage;

        // Sales/bills where tax_id explicitly references this tax
        $saleFlatById = (float) Sale::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('flatTax');

        $saleSubtotalById = (float) Sale::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('subtotal');

        $billFlatById = (float) Bill::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('flat_tax');

        $billSubtotalById = (float) Bill::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('subtotal');

        // If the percentage is unique across taxes, attribute NULL-tax_id rows with that percentage
        $saleFlatByPercent = 0;
        $saleSubtotalByPercent = 0;
        $billFlatByPercent = 0;
        $billSubtotalByPercent = 0;
        if (($percentageCounts[$pkey] ?? 0) === 1) {
          $saleFlatByPercent = floatval($saleFlatByPercentMap[$pkey] ?? 0);
          $saleSubtotalByPercent = floatval($saleSubtotalByPercentMap[$pkey] ?? 0);
          $billFlatByPercent = floatval($billFlatByPercentMap[$pkey] ?? 0);
          $billSubtotalByPercent = floatval($billSubtotalByPercentMap[$pkey] ?? 0);
        }

        $groupCollected = $saleFlatById + $saleFlatByPercent;
        $groupTotalSales = $saleSubtotalById + $saleSubtotalByPercent;

        $groupPaid = $billFlatById + $billFlatByPercent;
        $groupTotalPurchases = $billSubtotalById + $billSubtotalByPercent;

        $response[] = [
          'id' => $taxId,
          'name' => $tax->name,
          'percentage' => $percentage,
          'collected' => round($groupCollected, 2),
          'paid' => round($groupPaid, 2),
          'total_sales' => round($groupTotalSales, 2),
          'total_purchases' => round($groupTotalPurchases, 2),
        ];
      }

      // Add extra row for non-taxed metrics (tax_id = 0, "No Tax").
      // Also attach collected/paid/total_purchases computed from records with tax_id IS NULL
      $response[] = [
        'id' => 0,
        'name' => 'No Tax',
        'percentage' => 0,
        'collected' => round($saleFlatNullTotal, 2),
        'paid' => round($billFlatNullTotal, 2),
        'total_sales' => round($nonTaxedSales, 2),
        'total_purchases' => round($billSubtotalNullTotal, 2),
      ];

      $context = [
        'items' => $response,
      ];

      return Inertia::render("Accounting/Taxes", $context);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 400);
    }
  }

  public function list(Request $request)
  {
    $tax = $request->search;
    $taxes = Tax::where(function ($query) use ($tax) {
      $query->where('name', 'LIKE', '%' . $tax . '%');
      $query->orWhere('percentage', 'LIKE', '%' . $tax . '%');
    })->select('id', 'name', 'percentage')->get();

    // Convert to array to manipulate and prepend a No Tax option (id => null)
    $taxesArray = $taxes->toArray();

    // Avoid adding duplicate No Tax entries if backend or other code already provides it
    $hasNoTax = collect($taxesArray)->contains(function ($t) {
      return array_key_exists('id', $t) && ($t['id'] === null || $t['id'] === 0);
    });

    if (! $hasNoTax) {
      array_unshift($taxesArray, [
        'id' => null,
        'name' => 'No Tax',
        'percentage' => 0,
      ]);
    }

    return response()->json($taxesArray, 200);
  }

  /**
   * Store a newly created bills in storage.
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(Request $request)
  {
    try {
      $user = Auth::user();
      if ($request->taxes) {
        foreach ($request->taxes as $newTax) {
          $newTax = Tax::create([
            'name' => $newTax["name"],
            'percentage' => $newTax["percentage"],
            'user_id' => $user->id,
          ]);
        }
        return response()->json($request->taxes, 200);
      } else {
        $newTax = Tax::create([
          'name' => $request->name,
          'percentage' => $request->percentage,
          'user_id' => $user->id,
        ]);
        return response()->json($newTax, 200);
      }
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  public function update(Request $request)
  {
    try {
      foreach ($request->taxes as $tax) {
        $sales = Sale::where("tax_id", $tax['id']);
        if ($sales->get()) {
          $sales = $sales->get();
          foreach ($sales as $sale) {
            $newFlatTax = $sale->subtotal * $tax['percentage'] / 100;
            $newTotal = $sale->subtotal + $newFlatTax;
            $paid = 0;
            $newBalance = $newTotal;
            if ($sale->amount_paid >= $newTotal) {
              $paid = 1;
              $newBalance = 0;
            } else {
              $newBalance = $newBalance - $sale->amount_paid;
            }
            $update = $sale->update([
              'flatTax' => $newFlatTax,
              'tax' => $tax['percentage'],
              'total' => $newTotal,
              'paid' => $paid,
              'balance_remaning' => $newBalance,
            ]);
          }
        }
        $bills = Bill::where("tax_id", $tax['id']);
        if ($bills->get()) {
          $bills = $bills->get();
          foreach ($bills as $bill) {
            $newFlatTax = $bill->subtotal * $tax['percentage'] / 100;
            $newTotal = $bill->subtotal + $newFlatTax;
            $paid = 0;
            $newBalance = $newTotal;
            if ($bill->amount_paid >= $newTotal) {
              $paid = 1;
              $newBalance = 0;
            } else {
              $newBalance = $newBalance - $bill->amount_paid;
            }
            $sale->update([
              'flat_tax' => $newFlatTax,
              'tax' => $tax['percentage'],
              'total' => $newTotal,
              'paid' => $paid,
              'balance_remaning' => $newBalance,
            ]);
          }
        }
        $update = Tax::whereId($tax['id'])->update([
          'name' => $tax['name'],
          'percentage' => $tax['percentage'],
        ]);
      }
      return response()->json($update, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function remove(Request $request)
  {
    try {
      $code = 200;
      foreach ($request->taxes as $tax) {
        $sales = Sale::where("tax_id", $tax['id']);
        $bills = Bill::where("tax_id", $tax['id']);
        if ($sales->count() > 0 || $bills->count() > 0) {
          if ($sales->count() > 0) {
            $sales->update(['tax_id' => null]);
          }

          if ($bills->count() > 0) {
            $bills->update(['tax_id' => null]);
          }
          $delete = Tax::whereId($tax['id'])->delete();
          $code = 201;
        } else {
          $delete = Tax::whereId($tax['id'])->delete();
        }
      }
      return response()->json($delete, $code);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  public function datewise(Request $request)
  {
    try {
      $start = Carbon::parse($request->start)
                       ->startOfDay()
                       ->toDateTimeString();
      $end = Carbon::parse($request->end)
                     ->endOfDay()
                     ->toDateTimeString();
      $user = Auth::user();
      $taxes = Tax::where('user_id', $user->id)->get();
      $response = [];
      
      Log::info("Start date: $start, End date: $end");

      // Align non-taxed sales calculation with Dashboard for the requested date range
      $nonTaxedSales = DB::table('items')
        ->leftJoin('sales', 'items.sale_id', '=', 'sales.id')
        ->where('items.user_id', $user->id)
        ->whereBetween('items.sold', [$start, $end])
        ->where(function($q) {
          $q->whereNull('sales.tax')->orWhere('sales.tax', 0);
        })
        ->selectRaw('COALESCE(SUM(items.selling_price), 0) as s')
        ->value('s');

      // For each tax, compute sums for the given date range. We include
      // sales/bills that have tax_id equal to the tax id, and additionally
      // rows with tax_id = NULL that carry the same percentage value.
      foreach ($taxes as $tax) {
        $taxId = $tax->id;
        $percentage = (float) $tax->percentage;

        // Sales with explicit tax_id
        $saleFlatById = (float) Sale::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('flatTax');

        $saleSubtotalById = (float) Sale::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('subtotal');

        // Sales with null tax_id but matching percentage
        $saleFlatByPercent = (float) Sale::where('user_id', $user->id)
          ->whereNull('tax_id')
          ->where('tax', $percentage)
          ->whereBetween('date', [$start, $end])
          ->sum('flatTax');

        $saleSubtotalByPercent = (float) Sale::where('user_id', $user->id)
          ->whereNull('tax_id')
          ->where('tax', $percentage)
          ->whereBetween('date', [$start, $end])
          ->sum('subtotal');

        // Bills with explicit tax_id
        $billFlatById = (float) Bill::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('flat_tax');

        $billSubtotalById = (float) Bill::where('user_id', $user->id)
          ->where('tax_id', $taxId)
          ->whereBetween('date', [$start, $end])
          ->sum('subtotal');

        // Bills with null tax_id but matching percentage
        $billFlatByPercent = (float) Bill::where('user_id', $user->id)
          ->whereNull('tax_id')
          ->where('tax', $percentage)
          ->whereBetween('date', [$start, $end])
          ->sum('flat_tax');

        $billSubtotalByPercent = (float) Bill::where('user_id', $user->id)
          ->whereNull('tax_id')
          ->where('tax', $percentage)
          ->whereBetween('date', [$start, $end])
          ->sum('subtotal');

        $groupCollected = $saleFlatById + $saleFlatByPercent;
        $groupTotalSales = $saleSubtotalById + $saleSubtotalByPercent;

        $groupPaid = $billFlatById + $billFlatByPercent;
        $groupTotalPurchases = $billSubtotalById + $billSubtotalByPercent;

        $response[] = [
          'id' => $taxId,
          'name' => $tax->name,
          'percentage' => $percentage,
          'collected' => round($groupCollected, 2),
          'paid' => round($groupPaid, 2),
          'total_sales' => round($groupTotalSales, 2),
          'total_purchases' => round($groupTotalPurchases, 2),
        ];
      }

      // Add extra row for non-taxed metrics (tax_id = 0, "No Tax")
      $response[] = [
        'id' => 0,
        'name' => 'No Tax',
        'percentage' => 0,
        'collected' => 0,
        'paid' => 0,
        'total_sales' => round($nonTaxedSales, 2),
        'total_purchases' => 0,
      ];

      return response()->json($response, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 400);
    }
  }
}
