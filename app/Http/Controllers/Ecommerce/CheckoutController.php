<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmation;
use App\Models\Ecommerce\Market;
use App\Models\EcommerceSale;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a PaymentIntent for Stripe
     */
    public function createPaymentIntent(Request $request, string $slug)
    {
        $market = Market::where('slug', $slug)->firstOrFail();

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'customer' => 'required|array',
            'customer.email' => 'required|email',
            'customer.firstName' => 'required|string',
            'customer.lastName' => 'required|string',
            'customer.phone' => 'required|string',
        ]);

        // Real-time Stock Validation
        $itemIds = collect($request->items)->pluck('id');
        $unavailableItems = Item::whereIn('id', $itemIds)
            ->whereNotNull('sold')
            ->get();

        if ($unavailableItems->count() > 0) {
            return response()->json([
                'error' => 'Some items are no longer available',
                'unavailable_items' => $unavailableItems->pluck('model'),
            ], 400);
        }

        $currency = strtolower($market->currency ?? 'usd');

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) round($request->amount * 100),
                'currency' => $currency,
                'metadata' => [
                    'market_id' => $market->id,
                    'market_slug' => $slug,
                    'item_ids' => $itemIds->implode(','),
                    'customer_email' => $request->customer['email'],
                    'customer_first_name' => $request->customer['firstName'],
                    'customer_last_name' => $request->customer['lastName'],
                    'customer_phone' => $request->customer['phone'],
                    'customer_notes' => $request->customer['notes'] ?? '',
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent Error: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to create payment intent',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize the order after successful payment
     */
    public function storeOrder(Request $request, string $slug)
    {
        $market = Market::with('shop.company')->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'paymentIntentId' => 'required|string',
            'customer' => 'required|array',
            'customer.firstName' => 'required|string',
            'customer.lastName' => 'required|string',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'customer.notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'subtotal' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'shipping' => 'nullable|numeric',
            'total' => 'required|numeric',
        ]);

        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($validated['paymentIntentId']);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'error' => 'Payment not completed',
                    'status' => $paymentIntent->status,
                ], 400);
            }

            // Check if order already exists (to avoid duplicates if webhook already ran)
            $existingSale = EcommerceSale::where('payment_intent_id', $validated['paymentIntentId'])->first();
            if ($existingSale) {
                return $this->orderResponse($slug, $existingSale);
            }

            $sale = $this->createOrder($market, $paymentIntent, $validated['customer'], collect($validated['items'])->pluck('id')->toArray(), $validated);

            return $this->orderResponse($slug, $sale);

        } catch (ApiErrorException $e) {
            Log::error('Stripe Verify Error: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to verify payment',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Order Finalization Error: '.$e->getMessage());

            return response()->json(['error' => 'Failed to finalize order'], 500);
        }
    }

    /**
     * Core logic to create an order
     */
    protected function createOrder($market, $paymentIntent, $customerData, $itemIds, $totals = null)
    {
        return DB::transaction(function () use ($market, $paymentIntent, $customerData, $itemIds, $totals) {
            $ownerId = $market->shop->company->owner_id;

            $customerInfo = [
                'firstName' => $customerData['firstName'] ?? $customerData['customer_first_name'] ?? '',
                'lastName' => $customerData['lastName'] ?? $customerData['customer_last_name'] ?? '',
                'email' => $customerData['email'] ?? $customerData['customer_email'] ?? '',
                'phone' => $customerData['phone'] ?? $customerData['customer_phone'] ?? '',
                'notes' => $customerData['notes'] ?? $customerData['customer_notes'] ?? null,
            ];

            // If totals not provided (e.g. in webhook), use PaymentIntent amount
            $total = $totals['total'] ?? ($paymentIntent->amount / 100);
            $subtotal = $totals['subtotal'] ?? $total;

            $sale = EcommerceSale::create([
                'user_id' => $ownerId,
                'market_id' => $market->id,
                'subtotal' => $subtotal,
                'tax' => $totals['tax'] ?? 0,
                'total' => $total,
                'payment_method' => 'card',
                'amount_paid' => $total,
                'balance_remaining' => 0,
                'paid' => 1,
                'date' => now(),
                'notes' => json_encode($customerInfo), // Keep in notes for backward compatibility if needed, or remove
                'extra' => $customerInfo, // Store in extra column as JSON
                'payment_intent_id' => $paymentIntent->id,
                'channel' => 'ecommerce', // Channel for ecommerce sales
            ]);

            Item::whereIn('id', $itemIds)->update([
                'sale_id' => $sale->id,
                'sold' => now(),
                'customer' => $customerInfo['firstName'].' '.$customerInfo['lastName'],
            ]);

            // Send Confirmation Email
            try {
                Mail::to($customerInfo['email'])->send(new OrderConfirmation($sale));
            } catch (\Exception $e) {
                Log::error('Failed to send confirmation email: '.$e->getMessage());
            }

            return $sale;
        });
    }

    protected function orderResponse($slug, $sale)
    {
        $redirectUrl = URL::signedRoute('market.order.confirmation', [
            'market' => $slug,
            'sale_id' => $sale->id,
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $sale->id,
            'redirect_url' => $redirectUrl,
            'message' => 'Order completed successfully',
        ]);
    }

    /**
     * Get Stripe publishable key
     */
    public function getStripeKey(Request $request, string $slug)
    {
        return response()->json([
            'publishableKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Handle Stripe Webhooks
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');
        $endpointSecret = config('services.stripe.webhook.secret');

        $event = null;

        if ($endpointSecret && $sigHeader) {
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            $event = json_decode($payload, true);
        }

        $eventType = is_array($event) ? $event['type'] : $event->type;
        $eventData = is_array($event) ? $event['data']['object'] : $event->data->object;

        switch ($eventType) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($eventData);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($eventData);
                break;
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle successful payment (Webhook)
     */
    protected function handlePaymentSucceeded($paymentIntent)
    {
        // Check if order already exists
        $existingSale = EcommerceSale::where('payment_intent_id', $paymentIntent['id'])->first();
        if ($existingSale) {
            Log::info('Stripe Webhook: Order already exists for PaymentIntent '.$paymentIntent['id']);

            return;
        }

        $metadata = $paymentIntent['metadata'] ?? [];
        if (empty($metadata['item_ids'])) {
            Log::warning('Stripe Webhook: No item_ids in metadata for PaymentIntent '.$paymentIntent['id']);

            return;
        }

        $market = Market::with('shop.company')->find($metadata['market_id']);
        if (! $market) {
            Log::error('Stripe Webhook: Market not found for ID '.$metadata['market_id']);

            return;
        }

        $itemIds = explode(',', $metadata['item_ids']);

        $this->createOrder($market, (object) $paymentIntent, (array) $metadata, $itemIds);

        Log::info('Stripe Webhook: Order created via Webhook for PaymentIntent '.$paymentIntent['id']);
    }

    protected function handlePaymentFailed($paymentIntent)
    {
        Log::warning('Stripe Webhook: Payment failed', ['id' => $paymentIntent['id']]);
    }
}
