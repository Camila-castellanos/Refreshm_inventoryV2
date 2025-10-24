<?php

namespace App\Models\Ecommerce;

use App\Models\Shop;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Market extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'shop_id',
        'name',
        'custom_domain',
        'slug',
        'description',
        'tagline',
        'logo_url',
        'banner_url',
        'theme_colors',
        'is_active',
        'show_inventory_count',
        'currency',
        'tax_rate',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'contact_email',
        'contact_phone',
        'address',
        'return_policy',
        'shipping_policy',
        'privacy_policy',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'currency' => 'USD',
        'tax_rate' => 0.0,
        'is_active' => true,
        'show_inventory_count' => false,
        'theme_colors' => null,
        'meta_keywords' => null,
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'theme_colors' => 'array',
        'meta_keywords' => 'array',
        'is_active' => 'boolean',
        'show_inventory_count' => 'boolean',
        'tax_rate' => 'decimal:4',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Market $market) {
            if (empty($market->slug)) {
                $market->slug = $market->generateUniqueSlug($market->name);
            }
        });

        static::updating(function (Market $market) {
            if ($market->isDirty('name') && empty($market->slug)) {
                $market->slug = $market->generateUniqueSlug($market->name);
            }
        });
    }

    /**
     * Generate a unique slug for the consumer shop.
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
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Custom route model binding
     * For public routes (by slug): only show active markets
     * For admin routes (by id): show all markets for company access control
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Determine the field to search by
        $searchField = $field ?: 'slug';
        
        // For slug-based binding (public routes), filter by active status
        if ($searchField === 'slug') {
            return $this->where('slug', $value)->where('is_active', true)->first();
        }
        
        // For ID-based binding (admin routes), don't filter by active status
        // Company access control is handled in the controller
        return $this->where($searchField, $value)->first();
    }

    /**
     * Relationship: Consumer shop belongs to a business shop
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Relationship: Get company through shop
     */
    public function company(): BelongsTo
    {
        return $this->shop()->getRelated()->company();
    }

    /**
     * Relationship: Get items through shop
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(Item::class, Shop::class, 'id', 'shop_id', 'shop_id', 'id');
    }

    /**
     * Get published (available for sale) items
     */
    public function publishedItems()
    {
        return $this->items()
            ->whereNull('sold')
            ->whereNull('hold')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0);
    }

    /**
     * Get featured items for homepage
     */
    public function featuredItems(int $limit = 8)
    {
        return $this->publishedItems()
            ->with(['vendor'])
            ->latest()
            ->limit($limit);
    }

    /**
     * Get available product categories
     */
    public function getAvailableCategories()
    {
        return $this->publishedItems()
            ->whereNotNull('type')
            ->distinct('type')
            ->pluck('type')
            ->filter();
    }

    /**
     * Get items by category
     */
    public function getItemsByCategory(string $category)
    {
        return $this->publishedItems()
            ->where('type', $category)
            ->with(['vendor']);
    }

    /**
     * Get shop statistics for display
     */
    public function getStats(): array
    {
        $publishedItems = $this->publishedItems();
        
        return [
            'total_products' => $publishedItems->count(),
            'categories_count' => $this->getAvailableCategories()->count(),
            'price_range' => [
                'min' => $publishedItems->min('selling_price') ?? 0,
                'max' => $publishedItems->max('selling_price') ?? 0,
            ],
        ];
    }

    /**
     * Get the ecommerce URL
     */
    public function getUrlAttribute(): string
    {
        return route('ecommerce.index', $this->slug);
    }

    /**
     * Check if shop has available items
     */
    public function hasAvailableItems(): bool
    {
        return $this->publishedItems()->exists();
    }

    /**
     * Scope: Only active shops
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find market by custom domain
     */
    public function scopeByCustomDomain($query, string $host)
    {
        return $query->where('custom_domain', $host)->where('is_active', true);
    }

    /**
     * Find consumer shop by custom domain
     */
    public static function findByCustomDomain(string $host): ?self
    {
        return static::where('custom_domain', $host)->where('is_active', true)->first();
    }

    /**
     * Find consumer shop by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Get market data with safe defaults
     */
    public function getSafeData(): array
    {
        return [
            'id' => $this->id ?? null,
            'slug' => $this->slug ?? null,
            'name' => $this->name ?: 'Market Store',
            'description' => $this->description ?: 'Quality products at great prices',
            'tagline' => $this->tagline ?: 'Your trusted marketplace',
            'currency' => $this->currency ?: 'USD',
            'show_inventory_count' => $this->show_inventory_count ?: false,
            'is_active' => $this->is_active ?: false,
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'theme_colors' => $this->theme_colors ?: [
                'primary' => [
                    '50' => '#f0f9ff',
                    '500' => '#3b82f6',
                    '600' => '#2563eb',
                    '700' => '#1d4ed8',
                ]
            ],
            'meta_title' => $this->meta_title ?: ($this->name . ' - Online Market'),
            'meta_description' => $this->meta_description ?: ('Browse and shop ' . $this->name . ' collection of quality products.'),
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'return_policy' => $this->return_policy,
            'shipping_policy' => $this->shipping_policy,
            'custom_domain' => $this->custom_domain,
        ];
    }
}
