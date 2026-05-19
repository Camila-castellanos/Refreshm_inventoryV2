<?php

namespace App\Http\Middleware;

use App\Models\IncomingRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerOwnsResource
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the authenticated customer owns the resource they're trying to access.
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $customer = $request->user();

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $resourceId = $request->route('id') ?? $request->route($resourceType.'_id');

        if (! $resourceId) {
            return $next($request);
        }

        $ownsResource = match ($resourceType) {
            'incoming_request' => $this->customerOwnsRequest($customer->id, $resourceId),
            'sale' => $this->customerOwnsSale($customer->id, $resourceId),
            default => false,
        };

        if (! $ownsResource) {
            abort(403, 'No tienes acceso a este recurso.');
        }

        return $next($request);
    }

    /**
     * Check if customer owns the incoming request.
     */
    private function customerOwnsRequest(int $customerId, int $requestId): bool
    {
        return IncomingRequest::withoutGlobalScopes()
            ->where('id', $requestId)
            ->where('customer_id', $customerId)
            ->exists();
    }

    /**
     * Check if customer owns the sale.
     */
    private function customerOwnsSale(int $customerId, int $saleId): bool
    {
        $sale = \App\Models\Sale::withoutGlobalScopes()
            ->where('id', $saleId)
            ->first();

        if (! $sale) {
            return false;
        }

        // Check customer_id or email match
        return $sale->customer_id === $customerId
            || $sale->email === \App\Models\Customer::withoutGlobalScopes()->find($customerId)?->email;
    }
}