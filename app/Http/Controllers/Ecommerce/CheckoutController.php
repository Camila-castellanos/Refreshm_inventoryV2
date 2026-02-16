<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ecommerce\Market;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        ]);

        $currency = strtolower($market->currency ?? 'usd');

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) round($request->amount * 100),
                'currency' => $currency,
                'metadata' => [
                    'market_id' => $market->id,
                    'market_slug' => $slug,
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
        // Load market with shop and its company
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
        } catch (ApiErrorException $e) {
            Log::error('Stripe Verify Error: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to verify payment',
            ], 500);
        }

        return DB::transaction(function () use ($validated, $market, $slug) {
            // Get the company owner ID
            $ownerId = $market->shop->company->owner_id;

            $customer = Customer::updateOrCreate(
                ['email' => $validated['customer']['email']],
                [
                    'first_name' => $validated['customer']['firstName'],
                    'last_name' => $validated['customer']['lastName'],
                    'phone' => $validated['customer']['phone'],
                    'email' => $validated['customer']['email'],
                    'notes' => $validated['customer']['notes'] ?? null,
                    'user_id' => $ownerId,
                    'currency' => $market->currency,
                ]
            );

            $sale = Sale::create([
                'user_id' => $ownerId,
                'customer' => $validated['customer']['firstName'].' '.$validated['customer']['lastName'],
                'subtotal' => $validated['subtotal'],
                'tax' => $validated['tax'] ?? 0,
                'total' => $validated['total'],
                'payment_method' => 'card',
                'amount_paid' => $validated['total'],
                'balance_remaining' => 0,
                'paid' => 1,
                'date' => now(),
                'notes' => $validated['customer']['notes'] ?? null,
            ]);

            $itemIds = collect($validated['items'])->pluck('id');

            Item::whereIn('id', $itemIds)->update([
                'sale_id' => $sale->id,
                'sold' => now(),
                'customer' => $validated['customer']['firstName'].' '.$validated['customer']['lastName'],
            ]);

            // Generate signed URL for order confirmation page
            $redirectUrl = URL::signedRoute('market.order.confirmation', [
                'market' => $slug,
                'sale_id' => $sale->id,
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $sale->id,
                'redirect_url' => $redirectUrl, // Return the signed URL
                'message' => 'Order completed successfully',
            ]);
        });
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

        // Verify webhook signature if secret is configured
        if ($endpointSecret && $sigHeader) {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $endpointSecret
                );
            } catch (\UnexpectedValueException $e) {
                Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);

                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::error('Stripe Webhook: Invalid signature', ['error' => $e->getMessage()]);

                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            // If no secret configured, just decode the payload (for development only)
            $event = json_decode($payload, true);
        }

        // Handle the event
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event['data']['object']);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event['data']['object']);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['type' => $event['type']]);
                break;
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSucceeded($paymentIntent)
    {
        Log::info('Stripe Webhook: Payment succeeded', [
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => $paymentIntent['amount'],
            'metadata' => $paymentIntent['metadata'] ?? [],
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        Log::warning('Stripe Webhook: Payment failed', [
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => $paymentIntent['amount'],
            'last_payment_error' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
        ]);
    }

    /**
     * Handle refunded charge
     */
    protected function handleChargeRefunded($charge)
    {
        Log::info('Stripe Webhook: Charge refunded', [
            'charge_id' => $charge['id'],
            'amount_refunded' => $charge['amount_refunded'],
        ]);
    }
}
