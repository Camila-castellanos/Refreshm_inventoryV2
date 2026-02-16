<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Market;
use App\Models\EcommerceSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of ecommerce orders for a specific market.
     */
    public function index(Market $market, Request $request)
    {
        $start = $request->start ? Carbon::parse($request->start)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->end ? Carbon::parse($request->end)->endOfDay() : Carbon::now()->endOfDay();

        $orders = EcommerceSale::with(['items', 'items.vendor'])
            ->where('market_id', $market->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sale) {
                // Read from extra or fallback to notes
                $customerInfo = $sale->extra ?? json_decode($sale->notes, true);

                return [
                    'id' => $sale->id,
                    'customer' => $sale->customer,
                    'customer_email' => $customerInfo['email'] ?? $customerInfo['customer_email'] ?? '',
                    'total' => $sale->total,
                    'status' => $sale->paid ? 'Paid' : 'Pending',
                    'date' => $sale->created_at->format('Y-m-d H:i'),
                    'items_count' => $sale->items->count(),
                    'payment_intent' => $sale->payment_intent_id,
                ];
            });

        return Inertia::render('Ecommerce/Orders/Index', [
            'orders' => $orders,
            'market' => $market,
            'filters' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
        ]);
    }
}
