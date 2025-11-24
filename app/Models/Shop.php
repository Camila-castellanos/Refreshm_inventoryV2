<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // For the company relationship
use Illuminate\Database\Eloquent\Relations\HasMany; // For the items relationship
use Illuminate\Support\Str;

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
        'slug',
        'company_id',
        'address',
        'public_tabs',
        // Add other shop fields here if mass assignable
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        //
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'public_tabs' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Shop $shop) {
            if (empty($shop->slug)) {
                $shop->slug = $shop->generateUniqueSlug($shop->name);
            }
        });

        static::updating(function (Shop $shop) {
            if ($shop->isDirty('name') && empty($shop->slug)) {
                $shop->slug = $shop->generateUniqueSlug($shop->name);
            }
        });
    }

    /**
     * Generate a unique slug for the shop.
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Set the slug attribute.
     */
    public function setSlugAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['slug'] = $this->generateUniqueSlug($value);
        } elseif (!empty($this->name)) {
            $this->attributes['slug'] = $this->generateUniqueSlug($this->name);
        }
    }

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

    /**
     * Retrieve the model for a bound value.
     * This provides fallback: first try slug, then try id
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // First try to find by slug
        $shop = $this->where('slug', $value)->first();
        
        // If not found and value looks like an ID (numeric), try by ID
        if (!$shop && is_numeric($value)) {
            $shop = $this->where('id', $value)->first();
        }
        
        return $shop;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Find a shop by its slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}