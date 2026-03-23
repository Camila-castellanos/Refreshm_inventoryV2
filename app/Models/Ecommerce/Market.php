<?php

namespace App\Models\Ecommerce;

use App\Models\Item;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Market extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

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
        'faq',
        'about_us',
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
        'faq' => '{"title":"","description":"","questions":[]}',
        'about_us' => '{"title":"","content":"","image_url":null}',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'theme_colors' => 'array',
        'meta_keywords' => 'array',
        'faq' => 'array',
        'about_us' => 'array',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banners')
            ->useFallbackUrl('/images/banner-placeholder.jpg')
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(100)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(1920)
                    ->height(600)
                    ->sharpen(10)
                    ->nonQueued();
            });

        $this->addMediaCollection('logo')
            ->singleFile()
            ->useFallbackUrl('/images/logo-placeholder.png');

        $this->addMediaCollection('favicon')
            ->singleFile()
            ->useFallbackUrl('/favicon.ico');
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
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Set the slug attribute.
     */
    public function setSlugAttribute(?string $value): void
    {
        if (! empty($value)) {
            $this->attributes['slug'] = $this->generateUniqueSlug($value);
        } elseif (! empty($this->name)) {
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
     * If item has no MarketItem, we invert the current visibility value
     */
    public function toggleItemVisibility(int $itemId, ?bool $currentValue = null): bool
    {
        $marketItem = $this->marketItems()->where('item_id', $itemId)->first();

        if ($marketItem) {
            // If MarketItem exists, toggle the existing value
            return $marketItem->toggleVisibility();
        } else {
            // If doesn't exist and frontend calculated it as visible (true),
            // invert to hidden (false)
            // If frontend calculated it as hidden (false or null), set to visible (true)
            $newVisibility = ($currentValue !== true);

            $newMarketItem = $this->marketItems()->create([
                'item_id' => $itemId,
                'is_visible' => $newVisibility,
            ]);

            return $newMarketItem->is_visible;
        }
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
                'storage' => strtoupper($matches[2]),
            ];
        }

        // If no storage found, return the full model name
        return [
            'model' => trim($model),
            'storage' => null,
        ];
    }

    /**
     * Get items grouped by model with variant counts
     * Groups by parsed model (without storage) + manufacturer + type
     * Returns ONE item per model with aggregated data
     * Supports filtering by category, brand, search, and sorting
     *
     * @param  bool  $includeHidden  If true, includes hidden items (for admin) - default false (public view only)
     */
    public function getGroupedModels(?string $search = null, int $perPage = 20, ?string $category = null, ?string $brand = null, string $sort = 'latest', bool $includeHidden = false, ?string $modelFilter = null)
    {
        // First, get ALL items for photo counting (without price filters)
        $query = Item::where('shop_id', $this->shop_id)
            ->whereNull('sold')
            ->whereNull('hold')
            ->with('media')
            ->with('productModel.media');

        if ($modelFilter) {
            $query->where('model', 'like', "%{$modelFilter}%");
        }

        $allItems = $query->get();

        // Use all items (no selling_price filter - items without price are still valid)
        $items = $allItems;

        // Load market items for custom prices and visibility
        // Use $allItems to ensure we have marketItems for ALL items, not just filtered ones
        $marketItems = $this->marketItems()->whereIn('item_id', $allItems->pluck('id'))->get();
        $marketItemsMap = $marketItems->keyBy('item_id');

        // Default visible grades (when no MarketItem record exists)
        $defaultVisibleGrades = ['A', 'A-', 'B+', 'B'];

        // First, group ALL items (before filtering) to find photos
        $allGrouped = $allItems->groupBy(function ($item) {
            $parsed = $this->parseModelStorage($item->model);

            return $parsed['model'].'|'.$item->manufacturer.'|'.$item->type;
        });

        // Create a map of model groups with their photos
        $modelPhotoMap = $allGrouped->map(function ($group) {
            $firstItem = $group->first();
            $parsed = $this->parseModelStorage($firstItem->model);

            // 1. Try to get photo from ProductModel by colour (direct relation)
            $productModel = $firstItem->productModel;

            // Fallback: if no direct relation, search ProductModel by name (without storage)
            if (! $productModel && $parsed['model']) {
                $productModel = \App\Models\ProductModel::where('name', $parsed['model'])->first();
            }

            $productModelPhoto = null;
            $productModelPhotoCount = 0;

            if ($productModel && $productModel->hasPhotos()) {
                $productModelPhoto = $productModel->getFirstMediaUrl('product-photos', 'thumb');
                $productModelPhotoCount = $productModel->media->count();
            }

            // If ProductModel photo exists and is not a placeholder
            if ($productModelPhoto && $productModelPhoto !== asset('images/item-placeholder.svg')) {
                return [
                    'photo' => $productModelPhoto,
                    'photo_count' => $productModelPhotoCount,
                    'source' => 'product_model',
                    'product_model_id' => $productModel->id ?? null,
                ];
            }

            // 2. Fallback: find the first item with photos from ALL items
            $itemWithPhoto = $group->first(function ($item) {
                return $item->media->count() > 0;
            });
            $sharedPhoto = $itemWithPhoto ? $itemWithPhoto->getFirstMediaUrl('item-photos', 'thumb') : null;
            $sharedPhotoCount = $itemWithPhoto ? $itemWithPhoto->media->count() : 0;

            return [
                'photo' => $sharedPhoto,
                'photo_count' => $sharedPhotoCount,
                'source' => 'item',
                'product_model_id' => $productModel->id ?? null,
            ];
        });

        // Group by parsed model (without storage) + manufacturer + type
        // We group ALL items first, then filter to visible items within each group
        $grouped = $items->groupBy(function ($item) {
            $parsed = $this->parseModelStorage($item->model);

            return $parsed['model'].'|'.$item->manufacturer.'|'.$item->type;
        });

        // For public view, filter each group to keep only visible items
        // and exclude groups that have no visible items
        if (! $includeHidden) {
            $grouped = $grouped->map(function ($group) use ($marketItemsMap, $defaultVisibleGrades) {
                return $group->filter(function ($item) use ($marketItemsMap, $defaultVisibleGrades) {
                    $marketItem = $marketItemsMap[$item->id] ?? null;
                    $hasIssues = ! empty($item->issues) && $item->issues !== '{}';

                    // Filter: Hide items with battery < 80% unless explicitly visible
                    // If item has battery info and it's below 80%, it must have is_visible = true to show
                    $hasBatteryInfo = isset($item->battery) && $item->battery !== null && $item->battery !== '';
                    $lowBattery = $hasBatteryInfo && (int) $item->battery < 80;

                    if ($lowBattery) {
                        // Item has low battery - only show if is_visible is explicitly true
                        return $marketItem && $marketItem->is_visible === true;
                    }

                    // If item has issues, only include if is_visible is explicitly true
                    if ($hasIssues) {
                        if ($marketItem) {
                            return $marketItem->is_visible === true;
                        }

                        return false;
                    }

                    // If no issues, apply normal visibility logic
                    if ($marketItem) {
                        return $marketItem->is_visible === true;
                    }

                    // Default visibility based on grade (null counts as hidden)
                    return in_array($item->grade, $defaultVisibleGrades);
                });
            })->filter(fn ($group) => $group->count() > 0); // Remove groups with no visible items

        }

        // Apply search/category/brand filters AFTER grouping and visibility filtering
        // This ensures all items are considered for visibility before filtering by search/category/brand
        if ($search) {
            $grouped = $grouped->filter(function ($group) use ($search) {
                $firstItem = $group->first();

                return stripos($firstItem->model, $search) !== false ||
                       stripos($firstItem->manufacturer ?? '', $search) !== false ||
                       stripos($firstItem->type ?? '', $search) !== false;
            });
        }

        if ($category) {
            $grouped = $grouped->filter(fn ($group) => $group->first()->type === $category);
        }

        if ($brand) {
            $grouped = $grouped->filter(fn ($group) => $group->first()->manufacturer === $brand);
        }

        // Now map groups to model data, using only visible items for prices/counts
        $grouped = $grouped->map(function ($group, $groupKey) use ($marketItemsMap, $modelPhotoMap, $allGrouped) {
            $firstItem = $group->first();
            $parsed = $this->parseModelStorage($firstItem->model);

            // Count unique storage options
            $storageOptions = $group->pluck('model')
                ->map(fn ($m) => $this->parseModelStorage($m)['storage'])
                ->filter()
                ->unique()
                ->count();

            // Get prices using custom price or fallback to selling_price
            $prices = $group->map(function ($item) use ($marketItemsMap) {
                $marketItem = $marketItemsMap[$item->id] ?? null;

                return $marketItem ? $marketItem->getPrice() : $item->selling_price;
            });

            // Count total photos from ALL items in the group (including hidden ones)
            $allGroupItems = $allGrouped->get($groupKey, collect());
            $photoCount = $allGroupItems->reduce(function ($carry, $item) {
                return $carry + $item->media->count();
            }, 0);

            // Use the pre-calculated photo from the all-items grouping
            $photoData = $modelPhotoMap[$groupKey] ?? ['photo' => null, 'photo_count' => 0];

            return (object) [
                'model' => $parsed['model'],
                'manufacturer' => $firstItem->manufacturer,
                'type' => $firstItem->type,
                'total_stock' => $allGroupItems->count(), // Count ALL items (visible + hidden)
                'color_options' => $group->pluck('colour')->unique()->count(),
                'grade_options' => $group->pluck('grade')->unique()->count(),
                'storage_options' => $storageOptions,
                'min_price' => $prices->min(),
                'max_price' => $prices->max(),
                'avg_price' => $prices->avg(),
                'sample_item_id' => $group->min('id'),
                'photo' => $photoData['photo'],
                'photo_count' => $photoCount,
                'photo_source' => $photoData['source'] ?? null,
                'product_model_id' => $photoData['product_model_id'] ?? $firstItem->product_model_id,
                'id' => $group->min('id'),
            ];
        })->values();

        // Apply sorting to the grouped collection
        $sorted = match ($sort) {
            'price_low' => $grouped->sortBy('min_price'),
            'price_high' => $grouped->sortByDesc('max_price'),
            'name' => $grouped->sortBy('model'),
            default => $grouped->sort(function ($a, $b) {
                $normalize = function ($m) {
                    if (preg_match('/iPhone (X[RS]?)(.*)/i', $m, $matches)) {
                        $val = '10';
                        if (strtoupper($matches[1]) === 'XR') {
                            $val = '10.1';
                        }
                        if (strtoupper($matches[1]) === 'XS') {
                            $val = '10.2';
                        }

                        return 'iPhone '.$val.$matches[2];
                    }

                    return $m;
                };

                $normA = $normalize($a->model);
                $normB = $normalize($b->model);

                return strnatcasecmp($normB, $normA);
            }),
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
            $hasIssues = ! empty($item->issues) && $item->issues !== '{}';

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
     * Get model variants with all items - unified method for admin and public views
     *
     * @param  string  $model  The model name to search for
     * @param  bool  $includeHidden  If true, returns ALL items (admin view). If false, only visible items (public view)
     * @return array|null Returns null if no items found
     */
    public function getModelsWithVariants(string $model, bool $includeHidden = false): ?array
    {
        // Get all available items (not sold, not on hold)
        $allItems = Item::where('shop_id', $this->shop_id)
            ->whereNull('sold')
            ->whereNull('hold')
            ->with('media')
            ->with('productModel.media')
            ->get();

        // Parse model name and filter by matching model
        $parsedModel = $this->parseModelStorage(urldecode($model));
        $modelItems = $allItems->filter(function ($item) use ($parsedModel) {
            $parsed = $this->parseModelStorage($item->model);

            return $parsed['model'] === $parsedModel['model'];
        });

        if ($modelItems->isEmpty()) {
            return null;
        }

        // Load market items for custom prices and visibility
        $marketItemsMap = $this->marketItems()
            ->whereIn('item_id', $modelItems->pluck('id'))
            ->get()
            ->keyBy('item_id');

        // Conditions that should be visible by default
        $visibleConditions = ['A', 'A-', 'B+', 'B'];

        // Find the first item with photos from ALL items (including hidden ones)
        // This ensures the model photo is found even if the item with the photo is hidden
        $itemWithPhoto = $modelItems->first(function ($item) {
            return $item->media->count() > 0;
        });
        $sharedPhotoThumb = $itemWithPhoto ? $itemWithPhoto->getFirstMediaUrl('item-photos', 'thumb') : null;
        $sharedPhotoUrl = $itemWithPhoto ? $itemWithPhoto->getFirstMediaUrl('item-photos') : null;
        $sharedPhotoCount = $itemWithPhoto ? $itemWithPhoto->media->count() : 0;

        // Get product model info for photo fallback
        $firstItem = $modelItems->first();
        $productModel = $firstItem->productModel;

        // Map all items with their visibility status
        $mappedItems = $modelItems->map(function ($modelItem) use ($marketItemsMap, $visibleConditions, $sharedPhotoThumb, $sharedPhotoUrl, $sharedPhotoCount, $productModel) {
            $marketItem = $marketItemsMap[$modelItem->id] ?? null;
            $price = $marketItem ? $marketItem->getPrice() : $modelItem->selling_price;
            $hasIssues = ! empty($modelItem->issues) && $modelItem->issues !== '{}';

            // Determine visibility (null is treated as hidden/false)
            // Battery < 80% also hides unless explicitly visible via MarketItem
            // Battery null is treated as unknown (allowed, shows if grade is visible)
            if ($marketItem) {
                $isVisible = $marketItem->is_visible === true;
            } elseif ($hasIssues) {
                $isVisible = false;
            } elseif ($modelItem->battery !== null && $modelItem->battery < 80) {
                $isVisible = false;
            } else {
                $isVisible = in_array($modelItem->grade, $visibleConditions);
            }

            // Parse storage from model name
            $parsed = $this->parseModelStorage($modelItem->model);

            // Try to find ProductModel if not directly related
            // Fallback: if no direct relation, search ProductModel by name (without storage)
            $productModel = $modelItem->productModel;
            if (! $productModel && $parsed['model']) {
                $productModel = \App\Models\ProductModel::where('name', $parsed['model'])->first();
            }

            // Determine photo: item's own photo -> ProductModel photo -> shared photo
            // Check if item actually has photos (not just placeholder)
            $hasItemPhotos = $modelItem->media->count() > 0;
            $itemPhotoThumb = $hasItemPhotos ? $modelItem->getFirstMediaUrl('item-photos', 'thumb') : null;
            $itemPhotoUrl = $hasItemPhotos ? $modelItem->getFirstMediaUrl('item-photos') : null;

            // Try ProductModel photo by colour
            $productModelPhotoThumb = null;
            $productModelPhotoUrl = null;
            if ($productModel && $modelItem->colour) {
                $productModelPhotoThumb = $productModel->getFirstMediaUrlByColour($modelItem->colour, 'thumb');
                $productModelPhotoUrl = $productModel->getFirstMediaUrlByColour($modelItem->colour);
            }

            // Fallback order: item photo -> ProductModel photo -> shared photo
            $mainPhotoThumb = $itemPhotoThumb ?: ($productModelPhotoThumb ?: $sharedPhotoThumb);
            $mainPhotoUrl = $itemPhotoUrl ?: ($productModelPhotoUrl ?: $sharedPhotoUrl);

            // Calculate total photos for this item
            $itemPhotoCount = $modelItem->media->count();
            $productModelPhotoCount = $productModel ? $productModel->media->count() : 0;
            $totalPhotoCount = $itemPhotoCount ?: $productModelPhotoCount ?: $sharedPhotoCount;

            // Determine which photo source was used
            $photoSource = 'placeholder';
            if ($itemPhotoThumb) {
                $photoSource = 'item_photo';
            } elseif ($productModelPhotoThumb) {
                $photoSource = 'product_model_photo';
            } elseif ($sharedPhotoThumb) {
                $photoSource = 'shared_photo';
            }

            return [
                'id' => $modelItem->id,
                'model' => $modelItem->model,
                'manufacturer' => $modelItem->manufacturer,
                'imei' => $modelItem->imei,
                'type' => $modelItem->type,
                'colour' => $modelItem->colour,
                'condition' => $modelItem->grade,
                'storage' => $parsed['storage'] ?? null,
                'battery' => $modelItem->battery,
                'status' => $modelItem->sold ? 'sold' : ($modelItem->hold ? 'hold' : 'available'),
                'buying_price' => $modelItem->buying_price,
                'selling_price' => $modelItem->selling_price,
                'market_price' => $price,
                'has_custom_price' => $marketItem && $marketItem->custom_price !== null,
                'is_visible' => $isVisible,
                'description' => $marketItem ? $marketItem->description : null,
                'issues' => $modelItem->issues,
                // Photos with ProductModel fallback
                'photo_count' => $totalPhotoCount,
                'main_photo_thumb' => $mainPhotoThumb,
                'main_photo_url' => $mainPhotoUrl,
                'product_model_id' => $modelItem->productModel?->id,
            ];
        });

        // For public view, filter to only visible items
        if (! $includeHidden) {
            $mappedItems = $mappedItems->filter(fn ($item) => $item['is_visible'] === true);

            if ($mappedItems->isEmpty()) {
                return null;
            }
        }

        $firstItem = $modelItems->first();

        return [
            'model' => $parsedModel['model'],
            'manufacturer' => $firstItem->manufacturer,
            'type' => $firstItem->type,
            'product_model_id' => $productModel?->id,
            'items' => $mappedItems->values()->toArray(),
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
     * Get available models (grouped names)
     */
    public function getAvailableModels(?string $brand = null)
    {
        $query = $this->publishedItems();

        if ($brand) {
            $query->where('manufacturer', $brand);
        }

        $items = $query->get();
        $models = [];

        foreach ($items as $item) {
            $parsed = $this->parseModelStorage($item->model);
            if (! empty($parsed['model']) && ! in_array($parsed['model'], $models)) {
                $models[] = $parsed['model'];
            }
        }

        sort($models);

        return collect($models);
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
     * Get the banner_url attribute (backward compatibility)
     * Returns the first banner from the banners array
     */
    public function getBannerUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('banners');

        return $media ? $media->getUrl('large') : ($this->banners[0] ?? null);
    }

    /**
     * Get the logo_url attribute.
     * Prioritizes Spatie Media Library over the database column.
     */
    public function getLogoUrlAttribute($value): ?string
    {
        $media = $this->getFirstMedia('logo');

        if ($media) {
            return $media->getUrl();
        }

        return $value;
    }

    /**
     * Get the favicon_url attribute.
     * Prioritizes Spatie Media Library over the logo or default.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('favicon');

        if ($media) {
            return $media->getUrl();
        }

        // Fallback to logo if no favicon is set
        return $this->logo_url ?: asset('favicon.ico');
    }

    /**
     * Get market data with safe defaults
     */
    public function getSafeData(): array
    {
        // Get banner URLs from media library
        $banners = $this->getMedia('banners')->map(function ($media) {
            return $media->getUrl('large');
        })->toArray();

        // If no banners, check if there's a legacy banner_url stored in banners attribute (if any legacy data remained)
        if (empty($banners) && ! empty($this->attributes['banners'])) {
            $legacyBanners = json_decode($this->attributes['banners'], true);
            if (is_array($legacyBanners)) {
                $banners = $legacyBanners;
            }
        }

        return [
            'id' => $this->id ?? null,
            'shop_id' => $this->shop_id ?? null,
            'slug' => $this->slug ?? null,
            'name' => $this->name ?: 'Market Store',
            'description' => $this->description ?: 'Quality products at great prices',
            'tagline' => $this->tagline ?: 'Your trusted marketplace',
            'currency' => $this->currency ?: 'USD',
            'show_inventory_count' => $this->show_inventory_count ?: false,
            'is_active' => $this->is_active ?: false,
            'logo_url' => $this->logo_url,
            'favicon_url' => $this->favicon_url,
            'banners' => $banners,
            'about_us' => $this->about_us ?: [
                'title' => 'About Us',
                'content' => 'Welcome to '.($this->name ?: 'our store').'. We are dedicated to providing the best refurbished devices.',
                'image_url' => null,
            ],
            'media_banners' => $this->getMedia('banners')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl('large'),
                    'thumb' => $media->getUrl('thumb'),
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                ];
            }),
            'theme_colors' => $this->theme_colors ?: [
                'primary' => [
                    '50' => '#f0f9ff',
                    '500' => '#3b82f6',
                    '600' => '#2563eb',
                    '700' => '#1d4ed8',
                ],
            ],
            'meta_title' => $this->meta_title ?: ($this->name.' - Online Market'),
            'meta_description' => $this->meta_description ?: ('Browse and shop '.$this->name.' collection of quality products.'),
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'return_policy' => $this->return_policy,
            'shipping_policy' => $this->shipping_policy,
            'custom_domain' => $this->custom_domain,
            'faq' => $this->faq,
        ];
    }
}
