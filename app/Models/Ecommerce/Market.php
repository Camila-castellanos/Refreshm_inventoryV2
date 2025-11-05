<?php

namespace App\Models\Ecommerce;

use App\Models\Shop;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
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
     * Get items grouped by model with variant counts
     * Returns ONE item per model with aggregated data
     * Supports filtering by category, brand, search, and sorting
     */
    public function getGroupedModels(string $search = null, int $perPage = 20, string $category = null, string $brand = null, string $sort = 'latest')
    {
        $query = Item::where('shop_id', $this->shop_id)
            ->whereNull('sold')
            ->whereNull('hold')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('model', 'like', '%' . $search . '%')
                  ->orWhere('manufacturer', 'like', '%' . $search . '%')
                  ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        // Apply category filter (type field)
        if ($category) {
            $query->where('type', $category);
        }

        // Apply brand filter (manufacturer field)
        if ($brand) {
            $query->where('manufacturer', $brand);
        }

        // Get grouped data with aggregations
        $grouped = $query->select(
                'model',
                'manufacturer',
                'type',
                DB::raw('COUNT(*) as total_stock'),
                DB::raw('COUNT(DISTINCT colour) as color_options'),
                DB::raw('COUNT(DISTINCT grade) as grade_options'),
                DB::raw('MIN(selling_price) as min_price'),
                DB::raw('MAX(selling_price) as max_price'),
                DB::raw('AVG(selling_price) as avg_price'),
                DB::raw('MIN(id) as sample_item_id')
            )
            ->groupBy('model', 'manufacturer', 'type');

        // Apply sorting
        switch ($sort) {
            case 'price_low':
                $grouped->orderBy(DB::raw('MIN(selling_price)'), 'asc');
                break;
            case 'price_high':
                $grouped->orderBy(DB::raw('MAX(selling_price)'), 'desc');
                break;
            case 'name':
                $grouped->orderBy('model', 'asc');
                break;
            case 'latest':
            default:
                $grouped->orderByRaw('MIN(id) DESC'); // Most recent items first
                break;
        }

        $grouped = $grouped->paginate($perPage)->withQueryString();

        // Enhance each grouped result with media
        $grouped->setCollection($grouped->getCollection()->map(function ($model) {
            $item = Item::find($model->sample_item_id);
            $model->photo = $item ? $item->getFirstMediaUrl('item-photos', 'thumb') : null;
            $model->id = $model->sample_item_id; // Use sample item ID as the group ID
            return $model;
        }));

        return $grouped;
    }

    /**
     * Get all variants for a specific model
     * Groups by colour -> grade -> battery -> issues
     */
    public function getModelVariants(string $model)
    {
        $items = Item::where('shop_id', $this->shop_id)
            ->where('model', urldecode($model))
            ->whereNull('sold')
            ->whereNull('hold')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0)
            ->with('media')
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        // Group by colour
        $variants = $items->groupBy('colour')->map(function ($colorGroup) {
            return [
                'colour' => $colorGroup->first()->colour,
                'total' => $colorGroup->count(),
                'photo' => $colorGroup->first()->getFirstMediaUrl('item-photos', 'thumb'),
                'grades' => $colorGroup->groupBy('grade')->map(function ($gradeGroup) {
                    return [
                        'grade' => $gradeGroup->first()->grade,
                        'count' => $gradeGroup->count(),
                        'battery_options' => $gradeGroup->pluck('battery')->unique()->values(),
                        'issues' => $gradeGroup->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'issues' => $item->issues,
                                'count' => 1,
                            ];
                        })->unique('issues'),
                    ];
                })->values(),
            ];
        })->values();

        return [
            'model' => $items->first()->model,
            'manufacturer' => $items->first()->manufacturer,
            'type' => $items->first()->type,
            'total_stock' => $items->count(),
            'variants' => $variants,
        ];
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
