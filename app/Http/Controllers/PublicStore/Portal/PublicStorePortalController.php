<?php

namespace App\Http\Controllers\PublicStore\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\ReturnItems;
use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStorePortalController extends Controller
{
    /**
     * Get dashboard stats for the authenticated customer.
     */
    public function dashboard(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $totalRequests = IncomingRequest::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->count();

        $pendingRequests = IncomingRequest::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->where('customer_id', $customer->id)
            ->where('processed', false)
            ->count();

        // Credit balance from customer model
        $creditBalance = $customer->credit ?? 0;

        // Credit from returns
        $creditFromReturns = ReturnItems::withoutGlobalScopes()
            ->where('customer', $customer->email)
            ->sum('credit');

        // Total devices from processed (accepted) requests only
        $totalItemsCount = IncomingRequestItem::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->withTrashed()
            ->whereHas('request', function ($q) use ($customer) {
                $q->withoutGlobalScope(CompanyUsersSharedScope::class)
                  ->withTrashed()
                  ->where('customer_id', $customer->id)
                  ->where('processed', true);
            })
            ->count();

        // Recent requests (for dashboard preview - limited to 5)
        $recentRequests = IncomingRequest::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->with(['items' => function ($query) {
                $query->withTrashed();
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // All requests for the modal (limited to 50)
        $allRequests = IncomingRequest::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->with(['items' => function ($query) {
                $query->withTrashed();
            }])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Recently visited shops (limited to 5)
        $visitedShops = $customer->visitedShops()
            ->orderByPivot('last_visited_at', 'desc')
            ->limit(5)
            ->get();

        return \Inertia\Inertia::render('PublicStore/Portal/Dashboard', [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'total_items_count' => $totalItemsCount,
            'credit_balance' => $creditBalance,
            'credit_from_returns' => $creditFromReturns,
            'recent_requests' => $recentRequests,
            'all_requests' => $allRequests,
            'visited_shops' => $visitedShops,
        ]);
    }

    /**
     * List all requests for the authenticated customer.
     */
    public function requests(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $requests = IncomingRequest::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->with(['items' => function ($query) {
                $query->withTrashed();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return \Inertia\Inertia::render('PublicStore/Portal/Requests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Show a specific request with ownership check.
     */
    public function showRequest(Request $request, int $id)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $requestData = IncomingRequest::withoutGlobalScope(CompanyUsersSharedScope::class)
            ->withTrashed()
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->with(['items' => function ($query) {
                $query->withTrashed();
            }])
            ->first();

        if (! $requestData) {
            abort(403, "You don't have access to this request.");
        }

        return \Inertia\Inertia::render('PublicStore/Portal/Requests/Show', [
            'requestData' => $requestData,
            'items' => $requestData->items,
        ]);
    }

    /**
     * List returns for the authenticated customer.
     */
    public function returns(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $returns = ReturnItems::withoutGlobalScopes()
            ->where('customer', $customer->email)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return \Inertia\Inertia::render('PublicStore/Portal/Returns', [
            'returnsData' => $returns,
            'credit_total' => $returns->sum('credit'),
        ]);
    }

    /**
     * Get credit balance and transactions for the authenticated customer.
     */
    public function credit(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        // Get all credit from returns
        $creditFromReturns = ReturnItems::withoutGlobalScopes()
            ->where('customer', $customer->email)
            ->sum('credit');

        $transactions = collect([
            'credit_from_returns' => $creditFromReturns,
            'current_balance' => $customer->credit ?? 0,
        ]);

        return \Inertia\Inertia::render('PublicStore/Portal/Credit', [
            'balance' => $customer->credit ?? 0,
            'currency' => $customer->currency ?? 'CAD',
            'transactions' => $transactions,
        ]);
    }

    /**
     * List all orders for the authenticated customer.
     */
    public function orders(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $orders = \App\Models\Sale::withoutGlobalScopes()
            ->whereHas('items', function ($q) use ($customer) {
                $q->withoutGlobalScopes()
                  ->where('customer', $customer->email);
            })
            ->with(['items' => function ($q) {
                $q->withoutGlobalScopes();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return \Inertia\Inertia::render('PublicStore/Portal/Orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Update the authenticated customer's profile preferences.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'default_store' => ['nullable', 'string', 'max:255'],
            'default_shipping' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'in:CAD,USD'],
        ]);

        $customer->update($validated);

        return response()->json(['message' => 'Profile updated successfully']);
    }
}
