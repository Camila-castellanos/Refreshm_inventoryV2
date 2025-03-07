<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\Bill;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
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

      foreach ($taxes as $tax) {
        $paid = Bill::where('tax_id', $tax->id)->whereBetween('date', [$firstDayOfMonth, $currentDate])->where('user_id', $user->id)->sum('flat_tax');
        $collected = Sale::where('tax_id', $tax->id)->whereBetween('date', [$firstDayOfMonth, $currentDate])->where('user_id', $user->id)->sum('flatTax');
        $total_sales = round(Sale::where('tax_id', $tax->id)->where('user_id', $user->id)->sum('total'));
        $total_purchases = Bill::where('tax_id', $tax->id)->whereBetween('date', [$firstDayOfMonth, $currentDate])->where('user_id', $user->id)->sum('total');
        $i["id"] = $tax->id;
        $i["name"] = $tax->name;
        $i["percentage"] = (float)$tax->percentage;
        $i["collected"] = (float)$collected;
        $i["paid"] = (float)$paid;
        $i["total_sales"] = round((float)$total_sales, 2);
        $i["total_purchases"] = round((float)$total_purchases, 2);
        $response[] = $i;
      }

      $context = [
        'items' => $response,
      ];

      return Inertia::render("Accounting/TaxesShow", $context);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 400);
    }
  }

  public function list(Request $request)
  {
    $tax = $request->search;
    $taxes = Tax::whereUserId(Auth::id())->where(function ($query) use ($tax) {
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
      if ($request->newTaxes) {
        foreach ($request->newTaxes as $newTax) {
          $newTax = Tax::create([
            'name' => $newTax["name"],
            'percentage' => $newTax["percentage"],
            'user_id' => $user->id,
          ]);
        }
        return response()->json($request->newTaxes, 200);
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
      $start = $request->start;
      $end = $request->end;
      $user = Auth::user();
      $taxes = Tax::where('user_id', $user->id)->get();
      $response = [];

      foreach ($taxes as $tax) {
        $paid = Bill::where('tax_id', $tax->id)->whereBetween('date', [$start, $end])->where('user_id', $user->id)->sum('flat_tax');
        $collected = Sale::where('tax_id', $tax->id)->whereBetween('date', [$start, $end])->where('user_id', $user->id)->sum('flatTax');
        $total_sales = Sale::where('tax_id', $tax->id)->whereBetween('date', [$start, $end])->where('user_id', $user->id)->sum('total');
        $total_purchases = Bill::where('tax_id', $tax->id)->whereBetween('date', [$start, $end])->where('user_id', $user->id)->sum('total');
        $i["id"] = $tax->id;
        $i["name"] = $tax->name;
        $i["percentage"] = (float)$tax->percentage;
        $i["collected"] = (float)$collected;
        $i["paid"] = (float)$paid;
        $i["total_sales"] = round((float)$total_sales, 2);
        $i["total_purchases"] = round((float)$total_purchases, 2);
        $response[] = $i;
      }

      return response()->json($response, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 400);
    }
  }
}
