<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // For the company relationship
use Illuminate\Database\Eloquent\Relations\HasMany; // For the items relationship

class Shop extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 'company_id' is needed to link the shop when creating it.
     * 'name' and potentially 'address' are the shop's details.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'address',
        // Add other shop fields here if mass assignable
    ];

    /**
     * Define the relationship: A Shop BELONGS TO one Company.
     * Eloquent assumes the foreign key is 'company_id'.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Define the relationship: A Shop HAS MANY Items.
     * Eloquent assumes the foreign key on the 'items' table is 'shop_id'.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'shop_id');
    }
}