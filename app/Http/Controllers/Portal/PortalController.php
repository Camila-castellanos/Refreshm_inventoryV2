<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\ReturnItems;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    /**
     * Get dashboard stats for the authenticated customer.
     */
    public function dashboard(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $totalRequests = IncomingRequest::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->count();

        $pendingRequests = IncomingRequest::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('processed', false)
            ->count();

        // Credit balance from customer model
        $creditBalance = $customer->credit ?? 0;

        // Credit from returns
        $creditFromReturns = ReturnItems::withoutGlobalScopes()
            ->where('customer', $customer->email)
            ->sum('credit');

        // Total items requested across all requests
        $totalItemsCount = IncomingRequestItem::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->count();

        // Recent requests (for dashboard preview - limited to 5)
        $recentRequests = IncomingRequest::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // All requests for the modal (limited to 50)
        $allRequests = IncomingRequest::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return \Inertia\Inertia::render('PublicStore/Account/Dashboard', [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'total_items_count' => $totalItemsCount,
            'credit_balance' => $creditBalance,
            'credit_from_returns' => $creditFromReturns,
            'recent_requests' => $recentRequests,
            'all_requests' => $allRequests,
        ]);
    }

    /**
     * List all requests for the authenticated customer.
     */
    public function requests(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $requests = IncomingRequest::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return \Inertia\Inertia::render('PublicStore/Account/Requests/Index', [
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

        $requestData = IncomingRequest::withoutGlobalScopes()
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->with('items')
            ->first();

        if (! $requestData) {
            abort(403, "You don't have access to this request.");
        }

        return \Inertia\Inertia::render('PublicStore/Account/Requests/Show', [
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

        return \Inertia\Inertia::render('PublicStore/Account/Returns', [
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

        return \Inertia\Inertia::render('PublicStore/Account/Credit', [
            'balance' => $customer->credit ?? 0,
            'currency' => $customer->currency ?? 'CAD',
            'transactions' => $transactions,
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
        ]);

        $customer->update($validated);

        return response()->json(['message' => 'Profile updated successfully']);
    }
}