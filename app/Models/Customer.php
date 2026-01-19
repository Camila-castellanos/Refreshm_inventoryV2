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

    /**
     * Get sales related to this customer through items
     */
    public function sales()
    {
        return $this->hasManyThrough(
            Sale::class,
            Item::class,
            'customer', // Foreign key on items table
            'id',       // Foreign key on sales table  
            'id',       // Local key on customers table
            'sale_id'   // Local key on items table
        );
    }

    // For array

    // protected $casts = [
    //     'first_name' => 'array',
    //     'last_name' => 'array',
    //     'email' => 'array',
    //     'phone' => 'array',
    //     'phone_optional' => 'array'
    // ];

    /**
     * Accessor for phone - decodes JSON array if stored as string
     */
    public function getPhoneAttribute($value)
    {
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded[0];
            }
            return '';
        }
        return $value ?? '';
    }

    /**
     * Accessor for email - decodes JSON array if stored as string
     */
    public function getEmailAttribute($value)
    {
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded[0];
            }
            return '';
        }
        return $value ?? '';
    }

    /**
     * Accessor for first_name - decodes JSON array if stored as string
     */
    public function getFirstNameAttribute($value)
    {
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded[0];
            }
            return '';
        }
        return $value ?? '';
    }

    /**
     * Accessor for last_name - decodes JSON array if stored as string
     */
    public function getLastNameAttribute($value)
    {
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded[0];
            }
            return '';
        }
        return $value ?? '';
    }}
