<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\CashOnHand;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{
  /**
   * Handle the incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */

  public function __invoke()
  {
    $user = Auth::user();
    $startOfMonth = Carbon::now()
      ->startOfMonth()
      ->startOfDay()
      ->toDateTimeString();

    // $endOfMonth = date("Y-m-d");
    $today = \Carbon\Carbon::now(); //Current Date and Time
    $endOfMonth = \Carbon\Carbon::parse($today)->endOfMonth()->endOfDay()->toDateTimeString();

    // if(Auth::user()->role == 'USER' || 'ADMIN'){
    if (Auth::user()->role == 'USER') {
      return redirect('/inventory/items');
    }


    if (Auth::user()->role == 'ADMIN') {

      $items = Item::where('user_id', Auth::user()->id)->where([
        ["sold", ">=", $startOfMonth],
        ["sold", "<=", $endOfMonth],
      ])
        // ->whereHas('sale', function($query){
        //     $query->where('balance_remaining', 0);
        // })
        ->get();

      $alt_items = Item::where('user_id', Auth::user()->id)->get();

      $profit = 0;
      $soldvalue = 0;
      // $sales_id = [];
      $saleIdArray = [];
      $salesTotal = [];
      
       // Use helper for receivable / old database issues
      $accountsReceivable = $this->sumSalesBalanceRemaining(Auth::user()->id);
      // calculate account payable
      $billsWithPendingBalance = Bill::where('user_id', Auth::id())->where('status', 0)->get();
      $accountsPayable    = $billsWithPendingBalance->sum('balance_remaining');
      
      // taxed sales, non-taxed sales, total sales and profit calculations
       $non_taxed_sales_total = $items
        ->filter(fn($item) => !$item->sale || intval($item->sale->tax) === 0)
        ->sum(fn($item) => (float)$item->selling_price);

      $taxed_sales_total = $items
        ->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax) / 100));

      $soldvalue = $items
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100));

      $profit = $items
        ->sum(fn($item) => ((float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100)) - (float)$item->cost);

      // calculate total purchases
      $totalPurchases = Bill::where('user_id', Auth::user()->id)->sum('total');

      // calculate cost of goods sold
      $costOfGoodsSold = round($items->sum('cost'));
      $costOfTaxedGoodsSold = round($items->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)->sum('cost'));

      $devicesInInventory = Item::where('user_id', Auth::user()->id)->where('type', 'device')->whereNull("sold")->whereNull("hold")->count();
      $tradesThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->whereBetween("date", [$startOfMonth, $endOfMonth])->count();
      $soldThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->whereBetween("sold", [$startOfMonth, $endOfMonth])->count();
      $inventoryValue = round(Item::where('user_id', Auth::user()->id)->whereNull("sold")->sum("cost"));
      $saleValue = round(Item::where('user_id', Auth::user()->id)->whereNull("sold")->sum("selling_price"));
      $soldValueThisMonth = round($soldvalue);
      $cashOnHand = CashOnHand::select('balance')->where('user_id', Auth::user()->id)->value("balance");
      $profitThisMonth = round($profit);
      $accountsReceivable = round($accountsReceivable);
      $accountsReceivableThisMonth = round($accountsReceivable);
      $accountsPayableThisMonth = round($accountsPayable);
      $expensesThisMonth = round(Expense::where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('total'));
      $sale_ids = Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->distinct()->pluck('sale_id');
      $salesTaxCollected = round(Sale::whereNotNull('tax_id')->where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flatTax'));
      $salesTaxPaid = round(Bill::where('user_id', Auth::user()->id)->where('status', 1)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flat_tax'));
      $taxedSales = round($taxed_sales_total);
      $nonTaxedSales = round($non_taxed_sales_total);

    } else {

      $items = Item::where([
        ["sold", ">=", $startOfMonth],
        ["sold", "<=", $endOfMonth],
      ])
        // ->whereHas('sale', function($query){
        //     $query->where('balance_remaining', 0);
        // })
        ->get();

      $alt_items = Item::where('user_id', Auth::user()->id)->get();

      $profit = 0;
      $soldvalue = 0;
      // $sales_id = [];
      // $sales_id = [];
      $saleIdArray = [];
      $salesTotal = [];
      foreach ($alt_items as $item) {
        if ($item->sale_id != null) {
          $sale = Sale::where('id', $item->sale_id)->first();
          if ($sale && !in_array($sale->id, $salesTotal)) {
            array_push($salesTotal, $sale->id);
          }
        }
      }

      // Use helper for receivable / old database issues
      $accountsReceivable = $this->sumSalesBalanceRemaining(Auth::user()->id);
      // calculate account payable
      $billsWithPendingBalance = Bill::where('user_id', Auth::id())->where('status', 0)->get();
      $accountsPayable    = $billsWithPendingBalance->sum('balance_remaining');
      // taxed sales, non-taxed sales, total sales and profit calculations
      $non_taxed_sales_total = $items
        ->filter(fn($item) => !$item->sale || intval($item->sale->tax) === 0)
        ->sum(fn($item) => (float)$item->selling_price);

      $taxed_sales_total = $items
        ->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax) / 100));

      $soldvalue = $items
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100));

      $profit = $items
        ->sum(fn($item) => ((float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100)) - (float)$item->cost);

      // calculate cost of goods sold
      $costOfGoodsSold = round($items->sum('cost'));
      $costOfTaxedGoodsSold = round($items->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)->sum('cost'));

      // calculate total purchases
      $totalPurchases = Bill::where('user_id', Auth::user()->id)->sum('total');

      $devicesInInventory = Item::where('user_id', Auth::user()->id)->where('type', 'device')->whereNull("sold")->whereNull("hold")->count();
      $tradesThisMonth = Item::whereBetween("date", [$startOfMonth, $endOfMonth])->where('type', 'device')->count();
      $soldThisMonth = Item::whereBetween("sold", [$startOfMonth, $endOfMonth])->where('type', 'device')->count();
      $inventoryValue = round(Item::whereNull("sold")->sum("cost"));
      $saleValue = round(Item::whereNull("sold")->sum("selling_price"));
      $cashOnHand = CashOnHand::select('balance')->where('user_id', Auth::user()->id)->value("balance");
      $soldValueThisMonth = round($soldvalue);
      $profitThisMonth = round($profit);
      $accountsReceivableThisMonth = round($accountsReceivable);
      $accountsPayableThisMonth = round($accountsPayable);
      $expensesThisMonth = round(Expense::where('user_id', @$user->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('total'));
      $sale_ids = Item::where('user_id', @$user->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->distinct()->pluck('sale_id');
      $salesTaxCollected = round(Sale::whereNotNull('tax_id')->where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flatTax'));
      $salesTaxPaid = round(Bill::where('user_id', @$user->id)->where('status', 1)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flat_tax'));
      $taxedSales = round($taxed_sales_total);
      $nonTaxedSales = round($non_taxed_sales_total);
    }


    $context = [
      "devicesInInventory" => $devicesInInventory,
      "tradesThisMonth" => $tradesThisMonth,
      "soldThisMonth" => $soldThisMonth,
      "costSoldThisMonth" => $costOfGoodsSold,
      "costOfTaxedGoodsSold" => $costOfTaxedGoodsSold,
      "inventoryValue" => $inventoryValue,
      "saleValue" => $saleValue,
      "soldValueThisMonth" => $soldValueThisMonth,
      "profitThisMonth" => $profitThisMonth,
      'startDate' => $startOfMonth,
      'endDate' => $endOfMonth,
      'cashOnHand' => $cashOnHand,
      'expensesThisMonth' => $expensesThisMonth,
      'accountsReceivableThisMonth' => $accountsReceivableThisMonth,
      'accountsPayableThisMonth' => $accountsPayableThisMonth,
      'salesTaxCollected' => $salesTaxCollected,
      'salesTaxPaid' => $salesTaxPaid,
      'taxedSales' => $taxedSales,
      'nonTaxedSales' => $nonTaxedSales,
      'totalPurchases' => $totalPurchases
    ];

    return Inertia::render("Dashboard", $context);
  }

  public function updateCashOnHand(Request $request)
  {
    $user = Auth::user();
    $cash = CashOnHand::where('user_id', $user->id)->get();

    if ($cash->isEmpty()) {
      $context = CashOnHand::insert([
        'user_id' => $user->id,
        'balance' => $request->balance,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    } else {
      $context = CashOnHand::where('user_id', $user->id)->update([
        'balance' => $request->balance,
      ]);
    }

    return response()->json($context, 200);
  }

  public function repostDatewiseByDate(Request $request)
  {
    $user = Auth::user();
    $startOfMonth = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();

    // if(Auth::user()->role == 'USER' || 'ADMIN'){
    if (Auth::user()->role == 'USER') {
      return redirect('/inventory/items');
    }

    if (Auth::user()->role == 'ADMIN') {
      $items = Item::where('user_id', Auth::user()->id)->where([
        ["sold", "<=", $startOfMonth],
      ])->get();
      $profit = 0;
      $soldvalue = 0;

      // calculate account receivable and payable
      $salesWithPendingBalance = Sale::where('user_id', Auth::id())
      ->where('balance_remaining', '>', 0)
      ->get();
      $billsWithPendingBalance = Bill::where('user_id', Auth::id())
      ->where('status', 0)
      ->get();
      $accountsReceivable = $salesWithPendingBalance->sum('balance_remaining');
      $accountsPayable = $billsWithPendingBalance->sum('balance_remaining');

      // taxed sales, non-taxed sales, total sales and profit calculations
      $non_taxed_sales_total = $items
        ->filter(fn($item) => !$item->sale || intval($item->sale->tax) === 0)
        ->sum(fn($item) => (float)$item->selling_price);

      $taxed_sales_total = $items
        ->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax) / 100));

      $soldvalue = $items
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100));

      $profit = $items
        ->sum(fn($item) => ((float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100)) - (float)$item->cost);
      
        // calculate cost of goods sold
      $costOfGoodsSold = round($items->sum('cost'));
      $costOfTaxedGoodsSold = round($items->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)->sum('cost'));

      // calculate total purchases
      $totalPurchases = Bill::where('user_id', Auth::user()->id)->where('date', '<=', $startOfMonth)->sum('total');

      $devicesInInventory = Item::where('user_id', Auth::user()->id)
      ->where('type', 'device')
        ->where('date', '<=', $startOfMonth)
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('sold')->orWhere('sold', '>=', $startOfMonth);
        })
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('hold')->orWhere('hold', '>=', $startOfMonth);
        })->count();
      $tradesThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->where("date", $startOfMonth)->count();
      $soldThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->where("sold", $startOfMonth)->count();
      $inventoryValue = round(Item::where('user_id', Auth::user()->id)->where('date', '<=', $startOfMonth)
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('sold')->orWhere('sold', '>=', $startOfMonth);
        })
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('hold')->orWhere('hold', '>=', $startOfMonth);
        })->sum("cost"));
      $saleValue = round(Item::where('user_id', Auth::user()->id)->where('date', '<=', $startOfMonth)
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('sold')->orWhere('sold', '>=', $startOfMonth);
        })
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('hold')->orWhere('hold', '>=', $startOfMonth);
        })->sum("selling_price"));
      // $soldValueThisMonth = round(Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("selling_price"));
      $soldValueThisMonth = round($soldvalue);
      // $profitThisMonth = round(Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("profit"));
      $profitThisMonth = round($profit);
      $accountsReceivableThisMonth = round($accountsReceivable);
      $accountsPayableThisMonth = round($accountsPayable);
      $expensesThisMonth = round(Expense::where('user_id', @$user->id)->where("date", "<=", $startOfMonth)->sum('total'));
      $sale_ids = Item::where('user_id', @$user->id)->where("date", "<=", $startOfMonth)->distinct()->pluck('sale_id');
      $salesTaxCollected = round(Sale::whereNotNull('tax_id')->where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, Carbon::parse($startOfMonth)->endOfMonth()->toDateString()])->sum('flatTax'));
      $salesTaxPaid = round(Bill::where('user_id', Auth::user()->id)->where('status', 1)->where("date", "<=", $startOfMonth)->sum('flat_tax'));
      $taxedSales = round($taxed_sales_total);
      $nonTaxedSales = round($non_taxed_sales_total);
    } else {
      $items = Item::where('user_id', Auth::user()->id)->where([
        ["sold", "<=", $startOfMonth],
      ])->get();
      $profit = 0;
      $soldvalue = 0;
      
      // calculate account receivable and payable
      $salesWithPendingBalance = Sale::where('user_id', Auth::id())
      ->where('balance_remaining', '>', 0)
      ->get();
      $billsWithPendingBalance = Bill::where('user_id', Auth::id())
      ->where('status', 0)
      ->get();
      $accountsReceivable = $salesWithPendingBalance->sum('balance_remaining');
      $accountsPayable = $billsWithPendingBalance->sum('balance_remaining');

      // taxed sales, non-taxed sales, total sales and profit calculations
      $non_taxed_sales_total = $items
        ->filter(fn($item) => !$item->sale || intval($item->sale->tax) === 0)
        ->sum(fn($item) => (float)$item->selling_price);

      $taxed_sales_total = $items
        ->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax) / 100));

      $soldvalue = $items
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100));

      $profit = $items
        ->sum(fn($item) => ((float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100)) - (float)$item->cost);

        // calculate cost of goods sold
      $costOfGoodsSold = round($items->sum('cost'));
      $costOfTaxedGoodsSold = round($items->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)->sum('cost'));

       // calculate total purchases
      $totalPurchases = Bill::where('user_id', Auth::user()->id)->where('date', '<=', $startOfMonth)->sum('total');

      $devicesInInventory = Item::where('user_id', Auth::user()->id)
      ->where('type', 'device')
        ->where('date', '<=', $startOfMonth)
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('sold')->orWhere('sold', '>', $startOfMonth);
        })
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('hold')->orWhere('hold', '>', $startOfMonth);
        })->count();
      $tradesThisMonth = Item::where('user_id', Auth::user()->id)->where("date", $startOfMonth)->count();
      $soldThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->where("sold", $startOfMonth)->count();
      $inventoryValue = round(Item::where('user_id', Auth::user()->id)->where('date', '<=', $startOfMonth)
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('sold')->orWhere('sold', '>', $startOfMonth);
        })
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('hold')->orWhere('hold', '>', $startOfMonth);
        })->sum("cost"));
      $saleValue = round(Item::where('user_id', Auth::user()->id)->where('date', '<=', $startOfMonth)
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('sold')->orWhere('sold', '>=', $startOfMonth);
        })
        ->where(function ($query) use ($startOfMonth) {
          $query->whereNull('hold')->orWhere('hold', '>=', $startOfMonth);
        })->sum("selling_price"));
      // $soldValueThisMonth = round(Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("selling_price"));
      $soldValueThisMonth = round($soldvalue);
      // $profitThisMonth = round(Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("profit"));
      $profitThisMonth = round($profit);
      $accountsReceivableThisMonth = round($accountsReceivable);
      $accountsPayableThisMonth = round($accountsPayable);
      $expensesThisMonth = round(Expense::where('user_id', @$user->id)->where("date", "<=", $startOfMonth)->sum('total'));
      $sale_ids = Item::where('user_id', @$user->id)->where("date", "<=", $startOfMonth)->distinct()->pluck('sale_id');
      $salesTaxCollected = round(Sale::whereNotNull('tax_id')->where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, Carbon::parse($startOfMonth)->endOfMonth()->toDateString()])->sum('flatTax'));
      $salesTaxPaid = round(Bill::where('user_id', Auth::user()->id)->where('status', 1)->where("date", "<=", $startOfMonth)->sum('flat_tax'));
      $taxedSales = round($taxed_sales_total);
      $nonTaxedSales = round($non_taxed_sales_total);
    }


    $context = [
      "devicesInInventory" => $devicesInInventory,
      "tradesThisMonth" => $tradesThisMonth,
      "soldThisMonth" => $soldThisMonth,
      "costSoldThisMonth" => $costOfGoodsSold,
      "costOfTaxedGoodsSold" => $costOfTaxedGoodsSold,
      "inventoryValue" => $inventoryValue,
      "saleValue" => $saleValue,
      "soldValueThisMonth" => $soldValueThisMonth,
      "profitThisMonth" => $profitThisMonth,
      'startDate' => $startOfMonth,
      'expensesThisMonth' => $expensesThisMonth,
      'accountsReceivableThisMonth' => $accountsReceivableThisMonth,
      'accountsPayableThisMonth' => $accountsPayableThisMonth,
      'salesTaxCollected' => $salesTaxCollected,
      'salesTaxPaid' => $salesTaxPaid,
      'taxedSales' => $taxedSales,
      'nonTaxedSales' => $nonTaxedSales,
      'totalPurchases' => $totalPurchases,
    ];


    return response()->json($context, 200);
  }

  public function reportDatewise(Request $request)
  {
    $user = Auth::user();
    $startOfMonth = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
    $endOfMonth = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();

    // if(Auth::user()->role == 'USER' || 'ADMIN'){
    if (Auth::user()->role == 'USER') {
      return redirect('/inventory/items');
    }

    if (Auth::user()->role == 'ADMIN') {

      $items = Item::where('user_id', Auth::user()->id)->where([
        ["sold", ">=", $startOfMonth],
        ["sold", "<=", $endOfMonth],
      ])
      // ->whereHas('sale', function($query){
        //     $query->where('balance_remaining', 0);
        // })
        ->get();
      $profit = 0;
      $soldvalue = 0;
      
      // Use helper for receivable / old database issues
      $accountsReceivable = $this->sumSalesBalanceRemaining(Auth::user()->id);
      // calculate account payable
      $billsWithPendingBalance = Bill::where('user_id', Auth::id())->where('status', 0)->get();
      $accountsPayable    = $billsWithPendingBalance->sum('balance_remaining');

      // taxed sales, non-taxed sales, total sales and profit calculations
      $non_taxed_sales_total = $items
        ->filter(fn($item) => !$item->sale || intval($item->sale->tax) === 0)
        ->sum(fn($item) => (float)$item->selling_price);

      $taxed_sales_total = $items
        ->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax) / 100));

      $soldvalue = $items
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100));

      $profit = $items
        ->sum(fn($item) => ((float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100)) - (float)$item->cost);

      // calculate cost of goods sold
      $costOfGoodsSold = round($items->sum('cost'));
      $costOfTaxedGoodsSold = round($items->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)->sum('cost'));

       // calculate total purchases
      $totalPurchases = Bill::where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('total');

      $devicesInInventory = Item::where('user_id', Auth::user()->id)->whereNull("sold")->where('type', 'device')->whereNull("hold")->count();
      $tradesThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->whereBetween("date", [$startOfMonth, $endOfMonth])->count();
      $soldThisMonth = Item::where('user_id', Auth::user()->id)->where('type', 'device')->whereBetween("sold", [$startOfMonth, $endOfMonth])->count();
      $inventoryValue = round(Item::where('user_id', Auth::user()->id)->whereNull("sold")->sum("cost"));
      $saleValue = round(Item::where('user_id', Auth::user()->id)->whereNull("sold")->sum("selling_price"));
      // $soldValueThisMonth = round(Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("selling_price"));
      $soldValueThisMonth = round($soldvalue);
      // $profitThisMonth = round(Item::where('user_id', Auth::user()->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("profit"));
      $profitThisMonth = round($profit);
      $accountsReceivableThisMonth = round($accountsReceivable);
      $accountsPayableThisMonth = round($accountsPayable);
      $expensesThisMonth = round(Expense::where('user_id', @$user->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('total'));
      $sale_ids = Item::where('user_id', @$user->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->distinct()->pluck('sale_id');
      $salesTaxCollected = round(Sale::whereNotNull('tax_id')->where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flatTax'));
      $salesTaxPaid = round(Bill::where('user_id', Auth::user()->id)->where('status', 1)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flat_tax'));
      $taxedSales = round($taxed_sales_total);
      $nonTaxedSales = round($non_taxed_sales_total);
    } else {
      $items = Item::where([
        ["sold", ">=", $startOfMonth],
        ["sold", "<=", $endOfMonth],
      ])
        // ->whereHas('sale', function($query){
        //     $query->where('balance_remaining', 0);
        // })
        ->get();
        Log::info("Items datewise: " . $items->count());
      $profit = 0;
      $soldvalue = 0;
      
      // Use helper for receivable / old database issues
      $accountsReceivable = $this->sumSalesBalanceRemaining(Auth::user()->id);
      // calculate account payable
      $billsWithPendingBalance = Bill::where('user_id', Auth::id())->where('status', 0)->get();
      $accountsPayable    = $billsWithPendingBalance->sum('balance_remaining');

      // taxed sales, non-taxed sales, total sales and profit calculations
      $non_taxed_sales_total = $items
        ->filter(fn($item) => !$item->sale || intval($item->sale->tax) === 0)
        ->sum(fn($item) => (float)$item->selling_price);

      $taxed_sales_total = $items
        ->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax) / 100));

      $soldvalue = $items
        ->sum(fn($item) => (float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100));

      $profit = $items
        ->sum(fn($item) => ((float)$item->selling_price * (1 + intval($item->sale->tax ?? 0) / 100)) - (float)$item->cost);

    // calculate cost of goods sold
      $costOfGoodsSold = round($items->sum('cost'));
      $costOfTaxedGoodsSold = round($items->filter(fn($item) => $item->sale && intval($item->sale->tax) > 0)->sum('cost'));

       // calculate total purchases
      $totalPurchases = Bill::where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('total');

      $devicesInInventory = Item::where('user_id', Auth::user()->id)->whereNull("sold")->where('type', 'device')->whereNull("hold")->count();
      $tradesThisMonth = Item::whereBetween("date", [$startOfMonth, $endOfMonth])->where('type', 'device')->count();
      $soldThisMonth = Item::whereBetween("sold", [$startOfMonth, $endOfMonth])->where('type', 'device')->count();
      $inventoryValue = round(Item::whereNull("sold")->sum("cost"));
      $saleValue = round(Item::whereNull("sold")->sum("selling_price"));
      // $soldValueThisMonth = round(Item::whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("selling_price"));
      $soldValueThisMonth = round($soldvalue);
      // dump($startOfMonth);
      // dump($endOfMonth);
      // dd(Sale::whereBetween("created_at", [$startOfMonth, $endOfMonth])->get());
      // $profitThisMonth = round(Item::whereBetween("sold", [$startOfMonth, $endOfMonth])->sum("profit"));
      $profitThisMonth = round($profit);
      $accountsReceivableThisMonth = round($accountsReceivable);
      $accountsPayableThisMonth = round($accountsPayable);
      $expensesThisMonth = round(Expense::where('user_id', @$user->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('total'));
      $sale_ids = Item::where('user_id', @$user->id)->whereBetween("sold", [$startOfMonth, $endOfMonth])->distinct()->pluck('sale_id');
      $salesTaxCollected = round(Sale::whereNotNull('tax_id')->where('user_id', Auth::user()->id)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flatTax'));
      $salesTaxPaid = round(Bill::where('user_id', Auth::user()->id)->where('status', 1)->whereBetween("date", [$startOfMonth, $endOfMonth])->sum('flat_tax'));
      $taxedSales = round($taxed_sales_total);
      $nonTaxedSales = round($non_taxed_sales_total);
    }


    // dd($devicesInInventory);
    $context = [
      "devicesInInventory" => $devicesInInventory,
      "tradesThisMonth" => $tradesThisMonth,
      "soldThisMonth" => $soldThisMonth,
      "costSoldThisMonth" => $costOfGoodsSold,
      "costOfTaxedGoodsSold" => $costOfTaxedGoodsSold,
      "inventoryValue" => $inventoryValue,
      "saleValue" => $saleValue,
      "soldValueThisMonth" => $soldValueThisMonth,
      "profitThisMonth" => $profitThisMonth,
      'startDate' => $startOfMonth,
      'endDate' => $endOfMonth,
      'expensesThisMonth' => $expensesThisMonth,
      'accountsReceivableThisMonth' => $accountsReceivableThisMonth,
      'accountsPayableThisMonth' => $accountsPayableThisMonth,
      'salesTaxCollected' => $salesTaxCollected,
      'salesTaxPaid' => $salesTaxPaid,
      'taxedSales' => $taxedSales,
      'nonTaxedSales' => $nonTaxedSales,
      'totalPurchases' => $totalPurchases
    ];


    return response()->json($context, 200);
  }

    /**
     * Sums the balance_remaining of all sales with sold items,
     * ensuring there are no duplicate sales or sales with no items.
     * this because of old database issues
     */
  private function sumSalesBalanceRemaining(
      int     $userId,
      ?string $startSold = null,
      ?string $endSold   = null
  ): float {
      $itemQuery = Item::query()
          ->whereNotNull('sold')
          ->where('user_id', $userId)
          ->whereNotNull('sale_id');

      if ($startSold && $endSold) {
          $itemQuery->whereBetween('sold', [$startSold, $endSold]);
      }

      $saleIds = $itemQuery
          ->pluck('sale_id')
          ->unique();

      return $saleIds->reduce(function ($carry, $saleId) {
          $sale = Sale::find($saleId);
          return $carry + max(0, $sale->balance_remaining);
      }, 0);
  }
}


