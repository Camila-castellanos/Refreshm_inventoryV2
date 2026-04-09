<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\CashOnHand;
use App\Models\Expense;
use App\Models\Item;
use App\Models\LoginActivity;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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
        // Check if the user is authenticated
        $user = Auth::user();
        // save user id and role for later use
        $userId = $user->id;
        $isAdmin = $user->role === 'ADMIN';

        // initialize the start and end dates
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString();

        // cache and calculate metrics
        $cacheKey = "dashboard_metrics_v2_{$userId}_{$startOfMonth}_{$endOfMonth}";

        $context = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($userId, $isAdmin, $startOfMonth, $endOfMonth) {

            $salesMetrics = $this->calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
            $inventoryMetrics = $this->calculateInventoryMetrics($userId, $isAdmin);
            $deviceMetrics = $this->calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
            $financialMetrics = $this->calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth);

            return array_merge($salesMetrics, $inventoryMetrics, $deviceMetrics, $financialMetrics, [
                'startDate' => $startOfMonth,
                'endDate' => $endOfMonth,
            ]);
        });

        // Add user metrics separately without caching to avoid decryption/payload issues
        $userMetrics = $user->role === 'OWNER' ? $this->calculateUserMetrics($startOfMonth, $endOfMonth) : [];
        $context = array_merge($context, $userMetrics);

        return Inertia::render('Dashboard', $context);
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
        // save user id and role for later use
        $userId = $user->id;
        $isAdmin = $user->role === 'ADMIN';

        // initialize the start and end dates
        $startOfMonth = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
        if ($request->has('endDate') && $request->endDate) {
            $endOfMonth = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();
        } else {
            $endOfMonth = Carbon::parse($request->startDate)->endOfMonth()->endOfDay()->toDateTimeString();
        }

        // cache and calculate metrics
        $cacheKey = "dashboard_metrics_v2_{$userId}_{$startOfMonth}_{$endOfMonth}";

        $context = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($userId, $isAdmin, $startOfMonth, $endOfMonth) {
            $salesMetrics = $this->calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
            $inventoryMetrics = $this->calculateInventoryMetrics($userId, $isAdmin);
            $deviceMetrics = $this->calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
            $financialMetrics = $this->calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth);

            return array_merge($salesMetrics, $inventoryMetrics, $deviceMetrics, $financialMetrics, [
                'startDate' => $startOfMonth,
                'endDate' => $endOfMonth,
            ]);
        });

        // Add user metrics separately without caching
        $userMetrics = $user->role === 'OWNER' ? $this->calculateUserMetrics($startOfMonth, $endOfMonth) : [];
        $context = array_merge($context, $userMetrics);

        return response()->json($context, 200);
    }

    public function reportDatewise(Request $request)
    {
        // Check if the user is authenticated
        $user = Auth::user();
        // save user id and role for later use
        $userId = $user->id;
        $isAdmin = $user->role === 'ADMIN';

        // initialize the start and end dates
        $startOfMonth = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
        $endOfMonth = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();

        Log::info('Generating report for user: '.$userId, [
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
            'isAdmin' => $isAdmin,
            'userrole' => $user->role,
        ]);

        // cache and calculate metrics
        $cacheKey = "dashboard_metrics_v2_{$userId}_{$startOfMonth}_{$endOfMonth}";

        $context = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($userId, $isAdmin, $startOfMonth, $endOfMonth) {

            // Todos tus cálculos van aquí
            $salesMetrics = $this->calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
            $inventoryMetrics = $this->calculateInventoryMetrics($userId, $isAdmin);
            $deviceMetrics = $this->calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth);
            $financialMetrics = $this->calculateFinancialMetrics($userId, $startOfMonth, $endOfMonth);

            return array_merge($salesMetrics, $inventoryMetrics, $deviceMetrics, $financialMetrics, [
                'startDate' => $startOfMonth,
                'endDate' => $endOfMonth,
            ]);
        });

        // Add user metrics separately without caching
        $userMetrics = $user->role === 'OWNER' ? $this->calculateUserMetrics($startOfMonth, $endOfMonth) : [];
        $context = array_merge($context, $userMetrics);

        return response()->json($context, 200);
    }

    /**
     * Sums the balance_remaining of all sales with sold items,
     * ensuring there are no duplicate sales or sales with no items.
     * this because of old database issues
     */
    private function sumSalesBalanceRemaining(
        int $userId,
        ?string $startSold = null,
        ?string $endSold = null,
        bool $isAdmin = false
    ): float {
        $itemQuery = Item::whereNotNull('sold')
            ->whereNotNull('sale_id');

        if ($startSold && $endSold) {
            $itemQuery->whereBetween('sold', [$startSold, $endSold]);
        }

        $saleIds = $itemQuery
            ->pluck('sale_id')
            ->unique();

        return $saleIds->reduce(function ($carry, $saleId) {
            $sale = Sale::find($saleId);
            if ($sale) {
                return $carry + max(0, $sale->balance_remaining ?? 0);
            }

            return $carry;
        }, 0);
    }

    private function calculateSalesMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth)
    {
        // Una sola consulta para todas las métricas de ventas usando Eloquent + selectRaw
        $salesData = Item::leftJoin('sales', 'items.sale_id', '=', 'sales.id')
            ->whereBetween('items.sold', [$startOfMonth, $endOfMonth])
            ->selectRaw('
            COALESCE(SUM(
                CASE 
                    WHEN sales.tax_id IS NULL
                    THEN items.selling_price 
                    ELSE 0 
                END
            ), 0) as non_taxed_sales,
            COALESCE(SUM(
                CASE 
                    WHEN sales.tax_id IS NOT NULL 
                    THEN items.selling_price * (1 + sales.tax / 100) 
                    ELSE 0 
                END
            ), 0) as taxed_sales,
            COALESCE(SUM(
                CASE
                    WHEN sales.tax_id IS NOT NULL
                    THEN items.selling_price * (1 + sales.tax / 100)
                    ELSE items.selling_price
                END
            ), 0) as total_sold_value,
            COALESCE(SUM(
                (CASE
                    WHEN sales.tax_id IS NOT NULL
                    THEN items.selling_price * (1 + sales.tax / 100)
                    ELSE items.selling_price
                END) - COALESCE(items.cost, 0)
            ), 0) as total_profit,
            COALESCE(SUM(COALESCE(items.cost, 0)), 0) as cost_of_goods_sold,
            COALESCE(SUM(
                CASE 
                    WHEN sales.tax_id IS NOT NULL 
                    THEN COALESCE(items.cost, 0) 
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
        $inventoryData = Item::whereNull('sold')
            ->whereIn('type', ['device', 'accessory'])
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

    private function calculateDeviceMetrics($userId, $isAdmin, $startOfMonth, $endOfMonth)
    {
        // optimized aggregations for device items
        $deviceData = Item::whereIn('type', ['device'])
            ->selectRaw('
            COUNT(CASE WHEN (sold IS NULL) THEN 1 END) as devices_in_inventory,
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
        $accountsPayable = Bill::where('status', 0)
            ->sum('balance_remaining');

        // Efectivo en mano
        $cashOnHand = CashOnHand::value('balance') ?? 0;

        // Gastos del mes usando sum() con whereBetween
        $expensesThisMonth = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $expenseBreakdown = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('category, SUM(total) as total')
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn ($item) => [
                'category' => $item->category,
                'total' => round($item->total),
            ]);

        // Impuestos cobrados usando sum() condicional
        $salesTaxCollected = Sale::whereNotNull('tax_id')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('flatTax');

        Log::info('Calculating financial metrics for user: '.$userId, [
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
        ]);
        // Impuestos pagados
        $salesTaxPaid = Bill::where('status', 1)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('flat_tax');

        // Total de compras
        if ($allTime) {
            $totalPurchases = Bill::sum('total');
        } else {
            $totalPurchases = Bill::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->sum('total');
        }

        return [
            'cashOnHand' => $cashOnHand,
            'expensesThisMonth' => round($expensesThisMonth),
            'expenseBreakdown' => $expenseBreakdown,
            'accountsReceivableThisMonth' => round($accountsReceivable),
            'accountsPayableThisMonth' => round($accountsPayable),
            'salesTaxCollected' => round($salesTaxCollected),
            'salesTaxPaid' => round($salesTaxPaid),
            'totalPurchases' => $totalPurchases,
        ];
    }

    private function calculateUserMetrics($startOfMonth, $endOfMonth)
    {
        $newUsers = User::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $totalLogins = LoginActivity::whereBetween('login_at', [$startOfMonth, $endOfMonth])->count();

        $recentLogins = LoginActivity::with('user:id,name')
            ->orderBy('login_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($login) {
                // Apply IP masking at the server level for better security
                $ip = $login->ip_address;
                if ($ip) {
                    $parts = explode('.', $ip);
                    if (count($parts) === 4) {
                        $login->masked_ip = "{$parts[0]}.{$parts[1]}.***.***";
                    } else {
                        // Handle IPv6 or unexpected formats
                        $login->masked_ip = substr($ip, 0, 8).'...';
                    }
                } else {
                    $login->masked_ip = 'Unknown';
                }

                // Do not send the full decrypted IP to the frontend
                unset($login->ip_address);

                return $login;
            });

        return [
            'newUsers' => $newUsers,
            'totalLogins' => $totalLogins,
            'recentLogins' => $recentLogins,
        ];
    }
}
