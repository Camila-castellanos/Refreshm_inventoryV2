<?php

namespace App\Models;

class EcommerceSale extends Sale
{
    protected $table = 'sales';

    protected static function booted()
    {
        static::addGlobalScope('ecommerce', function ($query) {
            $query->where('channel', 'ecommerce');
        });
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'sale_id');
    }
}
