<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        "customer", "user_id", "company_id", "first_name", "last_name", "email", "phone", "phone_optional", "account_number", "website", "notes", "currency", "billing_address", "billing_address_optional", "billing_address_country", "billing_address_state", "billing_address_city", "billing_address_postal", "ship_name", "shipping_address", "shipping_address_optional", "shipping_address_country" ,"shipping_address_state", "shipping_address_city", "shipping_address_postal", "shipping_phone", "delivery_instructions", "credit",
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
