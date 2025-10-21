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
      $firstDayOfMonth = Carbon::now()->startOfMonth();
      $response = [];

      // Group tax records by name to avoid double-counting when same percentage appears in multiple tax rows
      $groups = [];
      foreach ($taxes as $tax) {
        $name = $tax->name;
        if (!isset($groups[$name])) {
          $groups[$name] = [
            'ids' => [],
            'percentages' => [],
            'id' => $tax->id,
            'percentage' => (float)$tax->percentage,
          ];
        }
        $groups[$name]['ids'][] = $tax->id;
        $groups[$name]['percentages'][] = (float)$tax->percentage;
      }

      // Precompute aggregated sums for all bills/sales (no date filters here)
      $billFlatById = Bill::where('user_id', $user->id)
        ->select('tax_id', DB::raw('SUM(flat_tax) as sum_flat'))
        ->groupBy('tax_id')
        ->pluck('sum_flat', 'tax_id')
        ->toArray();

      $billTotalById = Bill::where('user_id', $user->id)
        ->select('tax_id', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax_id')
        ->pluck('sum_total', 'tax_id')
        ->toArray();

      $saleFlatById = Sale::where('user_id', $user->id)
        ->select('tax_id', DB::raw('SUM(flatTax) as sum_flat'))
        ->groupBy('tax_id')
        ->pluck('sum_flat', 'tax_id')
        ->toArray();

      $saleTotalById = Sale::where('user_id', $user->id)
        ->select('tax_id', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax_id')
        ->pluck('sum_total', 'tax_id')
        ->toArray();

      // Aggregations for tax percentages where tax_id IS NULL
      $billFlatByPercent = Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(flat_tax) as sum_flat'))
        ->groupBy('tax')
        ->pluck('sum_flat', 'tax')
        ->toArray();

      $billTotalByPercent = Bill::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax')
        ->pluck('sum_total', 'tax')
        ->toArray();

      $saleFlatByPercent = Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(flatTax) as sum_flat'))
        ->groupBy('tax')
        ->pluck('sum_flat', 'tax')
        ->toArray();

      $saleTotalByPercent = Sale::where('user_id', $user->id)
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax')
        ->pluck('sum_total', 'tax')
        ->toArray();

      // Build percentage counts across all taxes to attribute percent-only matches only when unique
      $percentageCounts = [];
      foreach ($taxes as $tax) {
        $k = (string) ((float) $tax->percentage);
        $percentageCounts[$k] = ($percentageCounts[$k] ?? 0) + 1;
      }

      foreach ($groups as $name => $group) {
        $ids = array_values(array_unique($group['ids']));
        $percentages = array_values(array_unique($group['percentages']));
        // keep only percentages that are unique across all taxes
        $percentages = array_values(array_filter($percentages, function($p) use ($percentageCounts) {
          return ($percentageCounts[(string) ((float) $p)] ?? 0) === 1;
        }));

        $groupPaid = 0;
        $groupCollected = 0;
        $groupTotalSales = 0;
        $groupTotalPurchases = 0;

        foreach ($ids as $id) {
          $groupPaid += floatval($billFlatById[$id] ?? 0);
          $groupTotalPurchases += floatval($billTotalById[$id] ?? 0);
          $groupCollected += floatval($saleFlatById[$id] ?? 0);
          $groupTotalSales += floatval($saleTotalById[$id] ?? 0);
        }

        foreach ($percentages as $p) {
          $key = (string) ((float) $p);
          $groupPaid += floatval($billFlatByPercent[$key] ?? 0);
          $groupTotalPurchases += floatval($billTotalByPercent[$key] ?? 0);
          $groupCollected += floatval($saleFlatByPercent[$key] ?? 0);
          $groupTotalSales += floatval($saleTotalByPercent[$key] ?? 0);
        }

        $response[] = [
          'id' => $group['id'],
          'name' => $name,
          'percentage' => $group['percentage'],
          'collected' => round($groupCollected, 2),
          'paid' => round($groupPaid, 2),
          'total_sales' => round($groupTotalSales, 2),
          'total_purchases' => round($groupTotalPurchases, 2),
        ];
      }

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
    return response()->json($taxes, 200);
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

      // Build groups by name
      $groups = [];
      foreach ($taxes as $tax) {
        $name = $tax->name;
        if (!isset($groups[$name])) {
          $groups[$name] = [
            'ids' => [],
            'percentages' => [],
            'id' => $tax->id,
            'percentage' => (float)$tax->percentage,
          ];
        }
        $groups[$name]['ids'][] = $tax->id;
        $groups[$name]['percentages'][] = (float)$tax->percentage;
      }

      // Pre-aggregate sums for the date range to avoid per-group overlapping queries
      $billFlatById = Bill::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->select('tax_id', DB::raw('SUM(flat_tax) as sum_flat'))
        ->groupBy('tax_id')
        ->pluck('sum_flat', 'tax_id')
        ->toArray();

      $billTotalById = Bill::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->select('tax_id', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax_id')
        ->pluck('sum_total', 'tax_id')
        ->toArray();

      $saleFlatById = Sale::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->select('tax_id', DB::raw('SUM(flatTax) as sum_flat'))
        ->groupBy('tax_id')
        ->pluck('sum_flat', 'tax_id')
        ->toArray();

      $saleTotalById = Sale::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->select('tax_id', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax_id')
        ->pluck('sum_total', 'tax_id')
        ->toArray();

      // Aggregations for tax percentages where tax_id IS NULL
      $billFlatByPercent = Bill::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(flat_tax) as sum_flat'))
        ->groupBy('tax')
        ->pluck('sum_flat', 'tax')
        ->toArray();

      $billTotalByPercent = Bill::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax')
        ->pluck('sum_total', 'tax')
        ->toArray();

      $saleFlatByPercent = Sale::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(flatTax) as sum_flat'))
        ->groupBy('tax')
        ->pluck('sum_flat', 'tax')
        ->toArray();

      $saleTotalByPercent = Sale::where('user_id', $user->id)
        ->whereBetween('date', [$start, $end])
        ->whereNull('tax_id')
        ->select('tax', DB::raw('SUM(total) as sum_total'))
        ->groupBy('tax')
        ->pluck('sum_total', 'tax')
        ->toArray();

      // Build percentage counts across all taxes to attribute percent-only matches only when unique
      $percentageCounts = [];
      foreach ($taxes as $tax) {
        $k = (string) ((float) $tax->percentage);
        $percentageCounts[$k] = ($percentageCounts[$k] ?? 0) + 1;
      }

      foreach ($groups as $name => $group) {
        $ids = array_values(array_unique($group['ids']));
        $percentages = array_values(array_unique($group['percentages']));
        // keep only percentages that are unique across all taxes
        $percentages = array_values(array_filter($percentages, function($p) use ($percentageCounts) {
          return ($percentageCounts[(string) ((float) $p)] ?? 0) === 1;
        }));

        $groupPaid = 0;
        $groupCollected = 0;
        $groupTotalSales = 0;
        $groupTotalPurchases = 0;

        foreach ($ids as $id) {
          $groupPaid += floatval($billFlatById[$id] ?? 0);
          $groupTotalPurchases += floatval($billTotalById[$id] ?? 0);
          $groupCollected += floatval($saleFlatById[$id] ?? 0);
          $groupTotalSales += floatval($saleTotalById[$id] ?? 0);
        }

        foreach ($percentages as $p) {
          $key = (string) ((float) $p);
          $groupPaid += floatval($billFlatByPercent[$key] ?? 0);
          $groupTotalPurchases += floatval($billTotalByPercent[$key] ?? 0);
          $groupCollected += floatval($saleFlatByPercent[$key] ?? 0);
          $groupTotalSales += floatval($saleTotalByPercent[$key] ?? 0);
        }

        $response[] = [
          'id' => $group['id'],
          'name' => $name,
          'percentage' => $group['percentage'],
          'collected' => round($groupCollected, 2),
          'paid' => round($groupPaid, 2),
          'total_sales' => round($groupTotalSales, 2),
          'total_purchases' => round($groupTotalPurchases, 2),
        ];
      }

      return response()->json($response, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 400);
    }
  }
}
