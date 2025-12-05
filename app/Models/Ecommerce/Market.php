<?php

namespace App\Models\Ecommerce;

use App\Models\Shop;
use App\Models\Item;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Relationship: Market items with custom prices and visibility
     */
    public function marketItems(): HasMany
    {
        return $this->hasMany(MarketItem::class);
    }

    /**
     * Relationship: Custom prices for items in this market
     */
    public function itemPrices(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'market_items')
            ->withPivot('custom_price', 'is_visible')
            ->withTimestamps();
    }

    /**
     * Get the price for a specific item (custom price or fallback to selling_price)
     */
    public function getItemPrice(int $itemId): ?float
    {
        $marketItem = $this->marketItems()->where('item_id', $itemId)->first();
        
        if ($marketItem) {
            return $marketItem->getPrice();
        }

        // Fallback to item's selling_price
        $item = Item::find($itemId);
        return $item ? (float) $item->selling_price : null;
    }

    /**
     * Set a custom price for an item in this market
     */
    public function setItemPrice(int $itemId, float $price): void
    {
        $this->marketItems()->updateOrCreate(
            ['item_id' => $itemId],
            ['custom_price' => $price, 'is_visible' => true]
        );
    }

    /**
     * Toggle visibility for an item in this market
     */
    public function toggleItemVisibility(int $itemId): bool
    {
        $marketItem = $this->marketItems()->firstOrCreate(
            ['item_id' => $itemId],
            ['is_visible' => false]
        );

        return $marketItem->toggleVisibility();
    }

    /**
     * Set visibility for an item in this market
     */
    public function setItemVisibility(int $itemId, bool $isVisible): void
    {
        $this->marketItems()->updateOrCreate(
            ['item_id' => $itemId],
            ['is_visible' => $isVisible]
        );
    }

    /**
     * Remove custom price for an item (will use selling_price as fallback)
     */
    public function removeItemPrice(int $itemId): void
    {
        $marketItem = $this->marketItems()->where('item_id', $itemId)->first();
        
        if ($marketItem) {
            $marketItem->resetPrice();
        }
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
     * Parse model name to extract base model and storage capacity
     * Examples: "iPad 7 32GB" -> ['model' => 'iPad 7', 'storage' => '32GB']
     *           "iPhone 15 Pro Max 256GB" -> ['model' => 'iPhone 15 Pro Max', 'storage' => '256GB']
     *           "Galaxy S22 Ultra" -> ['model' => 'Galaxy S22 Ultra', 'storage' => null]
     */
    public function parseModelStorage(string $model): array
    {
        // Match patterns like: 32GB, 64GB, 128GB, 256GB, 512GB, 1TB, 2TB, etc.
        // This regex captures everything before the storage capacity as the model name
        if (preg_match('/^(.+?)\s+([\d]+(?:GB|TB))$/i', trim($model), $matches)) {
            return [
                'model' => trim($matches[1]),
                'storage' => strtoupper($matches[2])
            ];
        }
        
        // If no storage found, return the full model name
        return [
            'model' => trim($model),
            'storage' => null
        ];
    }

    /**
     * Get items grouped by model with variant counts
     * Groups by parsed model (without storage) + manufacturer + type
     * Returns ONE item per model with aggregated data
     * Supports filtering by category, brand, search, and sorting
     */
    public function getGroupedModels(string $search = null, int $perPage = 20, string $category = null, string $brand = null, string $sort = 'latest')
    {
        $query = Item::where('shop_id', $this->shop_id)
            ->whereNull('sold')
            ->whereNull('hold')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0)
            ->with('media');

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

        // Get all items first to parse models and extract storage
        $items = $query->get();

        // Load market items for custom prices and visibility
        $marketItems = $this->marketItems()->whereIn('item_id', $items->pluck('id'))->get();
        $marketItemsMap = $marketItems->keyBy('item_id');

        // Default visible grades (when no MarketItem record exists)
        $defaultVisibleGrades = ['A', 'A-', 'B+', 'B'];

        // Filter items: exclude those with issues unless is_visible = true
        $visibleItems = $items->filter(function ($item) use ($marketItemsMap, $defaultVisibleGrades) {
            $marketItem = $marketItemsMap[$item->id] ?? null;
            $hasIssues = !empty($item->issues) && $item->issues !== '{}';

            // If item has issues
            if ($hasIssues) {
                // Only include if is_visible is explicitly true
                if ($marketItem) {
                    return $marketItem->is_visible === true;
                }
                // If no MarketItem entry and has issues, exclude it
                return false;
            }

            // If no issues, apply normal visibility logic
            if ($marketItem) {
                // If MarketItem exists, use its is_visible flag
                return $marketItem->is_visible;
            }
            
            // Default visibility based on grade
            return in_array($item->grade, $defaultVisibleGrades);
        });
        
        // Group by parsed model (without storage) + manufacturer + type
        $grouped = $visibleItems->groupBy(function ($item) {
            $parsed = $this->parseModelStorage($item->model);
            return $parsed['model'] . '|' . $item->manufacturer . '|' . $item->type;
        })->map(function ($group) use ($marketItemsMap) {
            $firstItem = $group->first();
            $parsed = $this->parseModelStorage($firstItem->model);
            
            // Count unique storage options
            $storageOptions = $group->pluck('model')
                ->map(fn($m) => $this->parseModelStorage($m)['storage'])
                ->filter()
                ->unique()
                ->count();

            // Get prices using custom price or fallback to selling_price
            $prices = $group->map(function ($item) use ($marketItemsMap) {
                $marketItem = $marketItemsMap[$item->id] ?? null;
                return $marketItem ? $marketItem->getPrice() : $item->selling_price;
            });
            
            // Count total photos in the group
            $photoCount = $group->reduce(function ($carry, $item) {
                return $carry + $item->media->count();
            }, 0);
            
            return (object)[
                'model' => $parsed['model'],
                'manufacturer' => $firstItem->manufacturer,
                'type' => $firstItem->type,
                'total_stock' => $group->count(),
                'color_options' => $group->pluck('colour')->unique()->count(),
                'grade_options' => $group->pluck('grade')->unique()->count(),
                'storage_options' => $storageOptions,
                'min_price' => $prices->min(),
                'max_price' => $prices->max(),
                'avg_price' => $prices->avg(),
                'sample_item_id' => $group->min('id'),
                'photo' => $firstItem->getFirstMediaUrl('item-photos', 'thumb'),
                'photo_count' => $photoCount,
                'id' => $group->min('id'),
            ];
        })->values();

        // Apply sorting to the grouped collection
        $sorted = match($sort) {
            'price_low' => $grouped->sortBy('min_price'),
            'price_high' => $grouped->sortByDesc('max_price'),
            'name' => $grouped->sortBy('model'),
            default => $grouped->reverse(), // latest first
        };

        // Create pagination manually
        $page = request()->get('page', 1);
        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();
        
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
        
        return $paginated;
    }

    /**
     * Get all variants for a specific model
     * Groups by storage -> colour -> grade -> battery -> issues
     * Storage is now a selectable characteristic
     * Only returns items where is_visible is true
     */
    public function getModelVariants(string $model)
    {
        // First, get all items that match the parsed model name (without storage)
        $allItems = Item::where('shop_id', $this->shop_id)
            ->whereNull('sold')
            ->whereNull('hold')
            ->whereNotNull('selling_price')
            ->where('selling_price', '>', 0)
            ->with('media')
            ->get();

        // Filter items by matching the parsed model name
        $items = $allItems->filter(function ($item) use ($model) {
            $parsed = $this->parseModelStorage($item->model);
            return $parsed['model'] === urldecode($model);
        });

        if ($items->isEmpty()) {
            return null;
        }

        // Load market items for custom prices, visibility, and filtering
        $marketItems = $this->marketItems()->whereIn('item_id', $items->pluck('id'))->get();
        $marketItemsMap = $marketItems->keyBy('item_id');

        // Conditions that should be visible by default (if not configured)
        $visibleConditions = ['A', 'A-', 'B+', 'B'];

        // Filter items: exclude those with issues unless is_visible = true
        $items = $items->filter(function ($item) use ($marketItemsMap, $visibleConditions) {
            $marketItem = $marketItemsMap[$item->id] ?? null;
            $hasIssues = !empty($item->issues) && $item->issues !== '{}';

            // If item has issues
            if ($hasIssues) {
                // Only include if is_visible is explicitly true
                if ($marketItem) {
                    return $marketItem->is_visible === true;
                }
                // If no MarketItem entry and has issues, exclude it
                return false;
            }

            // If no issues, apply normal visibility logic
            if ($marketItem) {
                // If MarketItem exists, use its is_visible flag
                return $marketItem->is_visible;
            }
            
            // If no MarketItem entry, use default visibility based on grade
            return in_array($item->grade, $visibleConditions);
        });

        if ($items->isEmpty()) {
            return null;
        }

        // Find the first item with a photo to use as the model's main image
        $itemWithPhoto = $items->first(function ($item) {
            return $item->media->count() > 0;
        });
        $modelPhoto = $itemWithPhoto ? $itemWithPhoto->getFirstMediaUrl('item-photos', 'thumb') : null;
        $modelPhotoUrl = $itemWithPhoto ? $itemWithPhoto->getFirstMediaUrl('item-photos') : null;
        $modelPhotos = $itemWithPhoto ? $itemWithPhoto->media->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
            ];
        })->toArray() : [];

        // Group by storage (parsed from model name)
        $variants = $items->groupBy(function ($item) {
            $parsed = $this->parseModelStorage($item->model);
            return $parsed['storage'] ?? 'No Storage Info';
        })->map(function ($storageGroup) use ($marketItemsMap, $modelPhoto, $modelPhotoUrl, $modelPhotos) {
            // Then group by colour within each storage
            return [
                'storage' => $storageGroup->first() ? ($this->parseModelStorage($storageGroup->first()->model)['storage'] ?? 'No Storage Info') : null,
                'total' => $storageGroup->count(),
                'photo' => $modelPhoto,
                'colours' => $storageGroup->groupBy('colour')->map(function ($colorGroup) use ($marketItemsMap, $modelPhoto, $modelPhotoUrl, $modelPhotos) {
                    // Then group by grade within each colour
                    return [
                        'colour' => $colorGroup->first()->colour,
                        'count' => $colorGroup->count(),
                        'grades' => $colorGroup->groupBy('grade')->map(function ($gradeGroup) use ($marketItemsMap, $modelPhoto, $modelPhotoUrl, $modelPhotos) {
                            return [
                                'grade' => $gradeGroup->first()->grade,
                                'count' => $gradeGroup->count(),
                                'battery_options' => $gradeGroup->pluck('battery')->unique()->values(),
                                'issues' => $gradeGroup->map(function ($item) use ($marketItemsMap, $modelPhoto, $modelPhotoUrl, $modelPhotos) {
                                    // Use custom price or fallback to selling_price
                                    $marketItem = $marketItemsMap[$item->id] ?? null;
                                    $price = $marketItem ? $marketItem->getPrice() : $item->selling_price;
                                    return [
                                        'id' => $item->id,
                                        'issues' => $item->issues,
                                        'selling_price' => $price,
                                        'description' => $marketItem ? $marketItem->description : null,
                                        'count' => 1,
                                        // Use model's shared photo for all items
                                        'photo_count' => count($modelPhotos),
                                        'main_photo_thumb' => $modelPhoto,
                                        'main_photo_url' => $modelPhotoUrl,
                                        'photos' => $modelPhotos,
                                    ];
                                })->unique('id')->values(),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();

        $firstItem = $items->first();
        $parsed = $this->parseModelStorage($firstItem->model);

        return [
            'model' => $parsed['model'],
            'manufacturer' => $firstItem->manufacturer,
            'type' => $firstItem->type,
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
