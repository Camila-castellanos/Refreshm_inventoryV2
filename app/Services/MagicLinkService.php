<?php

namespace App\Services;

use App\Mail\MagicLinkEmail;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MagicLinkService
{
    /**
     * Token expiry in minutes.
     */
    public const TOKEN_EXPIRY_MINUTES = 15;

    /**
     * Generate a magic link token for a customer.
     */
    public function generateToken(Customer $customer): string
    {
        $token = Str::random(64);

        DB::table('customers')
            ->where('id', $customer->id)
            ->update([
                'remember_token' => $token,
                'magic_link_expires_at' => Carbon::now()->addMinutes(self::TOKEN_EXPIRY_MINUTES),
            ]);

        return $token;
    }

    /**
     * Validate a magic link token.
     * Returns the customer if valid, null if invalid or expired.
     */
    public function validateToken(string $token): ?Customer
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('remember_token', $token)
            ->whereNotNull('magic_link_expires_at')
            ->first();

        if (! $customer) {
            return null;
        }

        if (Carbon::now()->isAfter($customer->magic_link_expires_at)) {
            return null;
        }

        return $customer;
    }

    /**
     * Consume (invalidate) a magic link token.
     */
    public function consumeToken(Customer $customer): void
    {
        DB::table('customers')
            ->where('id', $customer->id)
            ->update([
                'remember_token' => null,
                'magic_link_expires_at' => null,
            ]);
    }

    /**
     * Send a magic link email to the customer.
     */
    public function sendMagicLink(Customer $customer, string $magicLinkUrl): void
    {
        Mail::to($customer->email)->send(new MagicLinkEmail($customer, $magicLinkUrl));
    }
}
