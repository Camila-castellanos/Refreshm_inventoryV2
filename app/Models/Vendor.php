<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        "user_id", "vendor", "first_name", "last_name", "email", "phone", "phone_optional", "website", "notes", "currency", "address", "address_optional", "address_country", "address_state", "address_city", "address_postal"
    ];

    protected $casts = [
        'first_name' => 'array',
        'last_name' => 'array',
        'email' => 'array',
        'phone' => 'array',
        'phone_optional' => 'array'
    ];

    public function items(): hasMany {
        return $this->hasMany(Item::class);
    }
}
