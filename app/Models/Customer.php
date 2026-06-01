<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'customer', 'user_id', 'company_id', 'first_name', 'last_name', 'email', 'phone', 'phone_optional', 'account_number', 'website', 'notes', 'currency', 'billing_address', 'billing_address_optional', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'ship_name', 'shipping_address', 'shipping_address_optional', 'shipping_address_country', 'shipping_address_state', 'shipping_address_city', 'shipping_address_postal', 'shipping_phone', 'delivery_instructions', 'credit',
        'default_store', 'default_shipping',
        // Auth fields
        'password',
        'email_verified_at',
        'remember_token',
        'magic_link_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
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
     * Get incoming requests for this customer.
     */
    public function incomingRequests(): HasMany
    {
        return $this->hasMany(IncomingRequest::class);
    }

    /**
     * Get incoming request items for this customer.
     */
    public function incomingRequestItems(): HasMany
    {
        return $this->hasMany(IncomingRequestItem::class);
    }

    /**
     * Get shops recently visited by this customer.
     */
    public function visitedShops()
    {
        return $this->belongsToMany(Shop::class, 'customer_visited_shops')
            ->withPivot('last_visited_at');
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
            'id', // Foreign key on sales table
            'id',       // Local key on customers table
            'sale_id'   // Local key on items table
        );
    }

    /**
     * Check if the customer has a password set.
     */
    public function hasPassword(): bool
    {
        return ! empty($this->password);
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
     * Accessor for phone - handles both arrays and JSON strings
     */
    public function getPhoneAttribute($value)
    {
        if (is_array($value)) {
            return $value[0] ?? '';
        }
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded[0];
            }

            return '';
        }

        return $value ?? '';
    }

    /**
     * Accessor for email - handles both arrays and JSON strings
     */
    public function getEmailAttribute($value)
    {
        if (is_array($value)) {
            return $value[0] ?? '';
        }
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded[0];
            }

            return '';
        }

        return $value ?? '';
    }

    /**
     * Accessor for first_name - handles both arrays and JSON strings
     */
    public function getFirstNameAttribute($value)
    {
        if (is_array($value)) {
            return $value[0] ?? '';
        }
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded[0];
            }

            return '';
        }

        return $value ?? '';
    }

    /**
     * Accessor for last_name - handles both arrays and JSON strings
     */
    public function getLastNameAttribute($value)
    {
        if (is_array($value)) {
            return $value[0] ?? '';
        }
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded[0];
            }

            return '';
        }

        return $value ?? '';
    }
}
