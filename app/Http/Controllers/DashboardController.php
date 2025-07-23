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
use Illuminate\Support\Facades\DB;


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

  try {
    // Check if the user is authenticated
    $user = Auth::user();
    // if the user is a normal user, redirect to inventory items  
    if ($user->role === 'USER') {
      return redirect('/inventory/items');
    }
    // save user id and role for later use
    $userId = $user->id;
    $isAdmin = $user->role === 'ADMIN' || $user->role === 'OWNER';  

    // initialize the start and end dates
    $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
    $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();

    // optimized calculations directly on sql
    $salesMetrics = $this->calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
    $inventoryMetrics = $this->calculateInventoryMetrics($userId, $isAdmin);
    $deviceMetrics = $this->calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
    $financialMetrics = $this->calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth, true, $isAdmin);

    // Contexto final
    $context = array_merge($salesMetrics, $inventoryMetrics, $deviceMetrics, $financialMetrics, [
        'startDate' => $startOfMonth,
        'endDate' => $endOfMonth,
    ]);

    return Inertia::render("Dashboard", $context);
  } catch (\Exception $e) {
    Log::error('Dashboard error: ' . $e->getMessage());
    return response()->json(['error' => 'An error occurred while loading the dashboard.'], 500);
  }
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
    // Check if the user is authenticated
    $user = Auth::user();
    // if the user is a normal user, redirect to inventory items  
    if ($user->role === 'USER') {
      return redirect('/inventory/items');
    }
    // save user id and role for later use
    $userId = $user->id;
    $isAdmin = $user->role === 'ADMIN' || $user->role === 'OWNER';  

    // initialize the start and end dates
    $startOfMonth = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
    if ($request->has('endDate') && $request->endDate) {
      $endOfMonth = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();
    } else {
      $endOfMonth = Carbon::parse($request->startDate)->endOfMonth()->endOfDay()->toDateTimeString();
    }

    // optimized calculations directly on sql
    $salesMetrics = $this->calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
    $inventoryMetrics = $this->calculateInventoryMetrics($userId, $isAdmin);
    $deviceMetrics = $this->calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
    $financialMetrics = $this->calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth, false, $isAdmin);

    $context = array_merge(
        $salesMetrics,
        $inventoryMetrics,
        $deviceMetrics,
        $financialMetrics,
        [
            'startDate' => $startOfMonth,
            'endDate' => $endOfMonth,
        ]
    );

    return response()->json($context, 200);
  }

  public function reportDatewise(Request $request)
  {
    // Check if the user is authenticated
    $user = Auth::user();
    // if the user is a normal user, redirect to inventory items  
    if ($user->role === 'USER') {
      return redirect('/inventory/items');
    }
    // save user id and role for later use
    $userId = $user->id;
    $isAdmin = $user->role === 'ADMIN' || $user->role === 'OWNER';  

    // initialize the start and end dates
    $startOfMonth = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
    $endOfMonth = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();


    Log::info('Generating report for user: ' . $userId, [
        'startOfMonth' => $startOfMonth,
        'endOfMonth' => $endOfMonth,
        'isAdmin' => $isAdmin,
        'userrole' => $user->role,
    ]);
    // optimized calculations directly on sql
    $salesMetrics = $this->calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
    $inventoryMetrics = $this->calculateInventoryMetrics($userId, $isAdmin);
    $deviceMetrics = $this->calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
    $financialMetrics = $this->calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth, false, $isAdmin);

    $context = array_merge(
        $salesMetrics,
        $inventoryMetrics,
        $deviceMetrics,
        $financialMetrics,
        [
            'startDate' => $startOfMonth,
            'endDate' => $endOfMonth,
        ]
    );

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
    ?string $endSold   = null,
    bool $isAdmin = false
  ): float {
    $itemQuery = Item::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
          ->whereNotNull('sold')
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

private function calculateSalesMetrics($userId, $isAdmin = false, $startOfMonth, $endOfMonth)
{
    // Una sola consulta para todas las métricas de ventas usando Eloquent + selectRaw
    $salesData = Item::when(true, fn($q) => $q->where('items.user_id', $userId))
        ->leftJoin('sales', 'items.sale_id', '=', 'sales.id')
        ->whereBetween('items.sold', [$startOfMonth, $endOfMonth])
        ->selectRaw('
            COALESCE(SUM(
                CASE 
                    WHEN sales.tax IS NULL OR sales.tax = 0 
                    THEN items.selling_price 
                    ELSE 0 
                END
            ), 0) as non_taxed_sales,
            COALESCE(SUM(
                CASE 
                    WHEN sales.tax > 0 
                    THEN items.selling_price * (1 + sales.tax / 100) 
                    ELSE 0 
                END
            ), 0) as taxed_sales,
            COALESCE(SUM(items.selling_price * (1 + COALESCE(sales.tax, 0) / 100)), 0) as total_sold_value,
            COALESCE(SUM((items.selling_price * (1 + COALESCE(sales.tax, 0) / 100)) - items.cost), 0) as total_profit,
            COALESCE(SUM(items.cost), 0) as cost_of_goods_sold,
            COALESCE(SUM(
                CASE 
                    WHEN sales.tax > 0 
                    THEN items.cost 
                    ELSE 0 
                END
            ), 0) as cost_of_taxed_goods_sold
        ')
        ->first();

    return [
        'soldValueThisMonth' => round($salesData->total_sold_value),
        'profitThisMonth' => round($salesData->total_profit),
        'costSoldThisMonth' => round($salesData->cost_of_goods_sold),
        'costOfTaxedGoodsSold' => round($salesData->cost_of_taxed_goods_sold),
        'taxedSales' => round($salesData->taxed_sales),
        'nonTaxedSales' => round($salesData->non_taxed_sales),
    ];
}

private function calculateInventoryMetrics($userId, $isAdmin = false)
{
    // Agregaciones simples para items en inventario
    $inventoryData = Item::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->whereNull('sold')
        ->selectRaw('
            COALESCE(SUM(cost), 0) as inventory_value,
            COALESCE(SUM(selling_price), 0) as sale_value
        ')
        ->first();

    return [
        'inventoryValue' => round($inventoryData->inventory_value),
        'saleValue' => round($inventoryData->sale_value),
    ];
}

private function calculateDeviceMetrics($userId, $isAdmin = false, $startOfMonth, $endOfMonth)
{
    // optimized aggregations for device items
    $deviceData = Item::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->where('type', 'device')
        ->selectRaw('
            COUNT(CASE WHEN (sold IS NULL AND hold IS NULL) THEN 1 END) as devices_in_inventory,
            COUNT(CASE WHEN date >= ? AND date <= ? THEN 1 END) as trades_this_month,
            COUNT(CASE WHEN sold >= ? AND sold <= ? THEN 1 END) as sold_this_month
        ', [$startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth])
        ->first();
    return [
        'devicesInInventory' => $deviceData->devices_in_inventory ?? 0,
        'tradesThisMonth' => $deviceData->trades_this_month ?? 0,
        'soldThisMonth' => $deviceData->sold_this_month ?? 0,
    ];
}

private function calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth, $allTime = false, $isAdmin = false)
{
    // Cuentas por cobrar (usando método helper existente)
    $accountsReceivable = $this->sumSalesBalanceRemaining($userId, null, null, $isAdmin);

    // Cuentas por pagar usando sum() directo
    $accountsPayable = Bill::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->where('status', 0)
        ->sum('balance_remaining');

    // Efectivo en mano
    $cashOnHand = CashOnHand::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->value('balance') ?? 0;

    // Gastos del mes usando sum() con whereBetween
    $expensesThisMonth = Expense::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->sum('total');

    // Impuestos cobrados usando sum() condicional
    $salesTaxCollected = Sale::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->whereNotNull('tax_id')
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->sum('flatTax');

    Log::info('Calculating financial metrics for user: ' . $userId, [
        'startOfMonth' => $startOfMonth,
        'endOfMonth' => $endOfMonth,
    ]);    
    // Impuestos pagados
    $salesTaxPaid = Bill::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->where('status', 1)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->sum('flat_tax');

    // Total de compras
    if ($allTime) {
    $totalPurchases = Bill::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))->sum('total');
    } else {
    $totalPurchases = Bill::when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total');
    }

    return [
        'cashOnHand' => $cashOnHand,
        'expensesThisMonth' => round($expensesThisMonth),
        'accountsReceivableThisMonth' => round($accountsReceivable),
        'accountsPayableThisMonth' => round($accountsPayable),
        'salesTaxCollected' => round($salesTaxCollected),
        'salesTaxPaid' => round($salesTaxPaid),
        'totalPurchases' => $totalPurchases,
    ];
}

}