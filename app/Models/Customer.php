<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyUsersSharedScope;
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        "customer", "user_id", "first_name", "last_name", "email", "phone", "phone_optional", "account_number", "website", "notes", "currency", "billing_address", "billing_address_optional", "billing_address_country", "billing_address_state", "billing_address_city", "billing_address_postal", "ship_name", "shipping_address", "shipping_address_optional", "shipping_address_country" ,"shipping_address_state", "shipping_address_city", "shipping_address_postal", "shipping_phone", "delivery_instructions", "credit",
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }

    // For array

    protected $casts = [
        'first_name' => 'array',
        'last_name' => 'array',
        'email' => 'array',
        'phone' => 'array',
        'phone_optional' => 'array'
    ];
}
