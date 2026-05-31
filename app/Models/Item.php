<?php

namespace App\Models;

use App\Models\Scopes\CompanyItemScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Item extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    // Constants for status
    const STATUS_AVAILABLE = 'available';
    const STATUS_RESERVED = 'reserved';
    const STATUS_SOLD = 'sold';

    protected $fillable = [
        'date', 'sale_id', 'supplier', 'manufacturer', 'storage_id', 'position',
        'model', 'colour', 'battery', 'grade',
        'issues', 'cost', 'imei', 'selling_price',
        'customer', 'sold', 'hold', 'discount', 'tax',
        'subtotal', 'profit', 'user_id', 'vendor_id', 'custom_values',
        'sold_storage_id', 'sold_position', 'sold_storage_name', 'shop_id',
        'type', 'product_model_id', 'status', 'partially_sold_at',
    ];

    // Cast date and sold attributes as full datetime
    protected $casts = [
        'date' => 'datetime',
        'sold' => 'datetime',
        'partially_sold_at' => 'datetime',
    ];

    // Append custom attributes to JSON
    protected $appends = [
        'main_photo_url',
        'main_photo_thumb',
        'photo_urls',
        'photo_count',
    ];

    protected $attributes = [
        'status' => 'available',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyItemScope);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }

    /**
     * Register media collections for item photos
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('item-photos')
            ->useFallbackUrl('/images/item-placeholder.svg')
            ->useFallbackPath(public_path('/images/item-placeholder.svg'))
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('preview')
                    ->width(800)
                    ->height(600)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('detail')
                    ->width(1200)
                    ->height(900)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }

    /**
     * Get the main item photo URL
     */
    public function getMainPhotoUrlAttribute(): string
    {
        // 1. Si tiene fotos propias, usar la primera
        $ownPhoto = $this->getFirstMediaUrl('item-photos', 'preview');
        if ($ownPhoto) {
            return $ownPhoto;
        }

        // 2. Si no, buscar foto del ProductModel por colour
        if ($this->productModel && $this->colour) {
            $modelPhoto = $this->productModel->getFirstMediaUrlByColour($this->colour, 'preview');
            if ($modelPhoto && $modelPhoto !== asset('images/item-placeholder.svg')) {
                return $modelPhoto;
            }
        }

        return asset('images/item-placeholder.svg');
    }

    /**
     * Get the main item photo thumbnail URL
     */
    public function getMainPhotoThumbAttribute(): string
    {
        // 1. Si tiene fotos propias, usar la primera
        $ownPhoto = $this->getFirstMediaUrl('item-photos', 'thumb');
        if ($ownPhoto) {
            return $ownPhoto;
        }

        // 2. Si no, buscar foto del ProductModel por colour
        if ($this->productModel && $this->colour) {
            $modelPhoto = $this->productModel->getFirstMediaUrlByColour($this->colour, 'thumb');
            if ($modelPhoto && $modelPhoto !== asset('images/item-placeholder.svg')) {
                return $modelPhoto;
            }
        }

        return asset('images/item-placeholder.svg');
    }

    /**
     * Get all item photos URLs
     */
    public function getPhotoUrlsAttribute(): array
    {
        return $this->getMedia('item-photos')
            ->map(function (Media $media) {
                return [
                    'id' => $media->id,
                    'original' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                    'preview' => $media->getUrl('preview'),
                    'detail' => $media->getUrl('detail'),
                    'name' => $media->file_name,
                    'size' => $media->size,
                ];
            })
            ->toArray();
    }

    /**
     * Check if item has photos
     */
    public function hasPhotos(): bool
    {
        return $this->getMedia('item-photos')->isNotEmpty();
    }

    /**
     * Get photo count
     */
    public function getPhotoCountAttribute(): int
    {
        return $this->getMedia('item-photos')->count();
    }

    public function getVendorNameAttribute()
    {
        return $this->vendor ? $this->vendor->vendor : null;
    }

    public function markAsReserved(): bool
    {
        $this->status = self::STATUS_RESERVED;
        // Position and storage_id are KEPT

        return $this->save();
    }

    public function markAsSold(): bool
    {
        $this->status = self::STATUS_SOLD;
        $this->storage_id = null;
        $this->position = null;

        return $this->save();
    }

    public function markAsAvailable(): bool
    {
        return $this->removeSale();
    }

    public function removeSale(): bool
    {
        if ($this->status === 'reserved') {
            $this->status = self::STATUS_AVAILABLE;
            $this->sale_id = null;
            $this->customer = null;
            $this->sold = null;
            $this->hold = null;
            $this->discount = null;
            $this->tax = null;
            $this->subtotal = null;
            $this->profit = null;

            return $this->saveOrFail();
        }

        // Attempt to restore original location if available
        $this->restorePositionIfPossible();

        $this->sale_id = null;
        $this->customer = null;
        $this->sold = null;
        $this->hold = null;
        $this->discount = null;
        $this->tax = null;
        $this->subtotal = null;
        $this->profit = null;
        $this->status = self::STATUS_AVAILABLE;

        return $this->saveOrFail();
    }

    /**
     * Revert a sold item back to reserved (e.g. payment reversed or removed).
     * Attempts to restore the original position if still free; otherwise
     * finds the next available slot in the same storage.
     */
    public function revertToReserved(): bool
    {
        $this->restorePositionIfPossible();

        $this->sold = null;
        $this->status = self::STATUS_RESERVED;

        return $this->saveOrFail();
    }

    /**
     * Try to restore the item to its original storage position.
     * If original is free → restore it. If occupied → next available in same storage.
     * If storage full → leave position null. Clears sold_* history on successful restore.
     */
    private function restorePositionIfPossible(): void
    {
        if ($this->sold_storage_id && $this->sold_position) {
            $isOccupiedByItem = self::where('storage_id', $this->sold_storage_id)
                ->where('position', $this->sold_position)
                ->where('id', '!=', $this->id)
                ->exists();

            $isOccupiedByDraft = DraftItem::where('storage_id', $this->sold_storage_id)
                ->where('storage_position', $this->sold_position)
                ->exists();

            if (! $isOccupiedByItem && ! $isOccupiedByDraft) {
                // Original position is free — restore it
                $this->storage_id = $this->sold_storage_id;
                $this->position = $this->sold_position;
                $this->sold_storage_id = null;
                $this->sold_position = null;
                $this->sold_storage_name = null;
            } else {
                // Original occupied — try next available in same storage
                $nextPos = self::getNextAvailablePosition($this->sold_storage_id);
                if ($nextPos) {
                    $this->storage_id = $this->sold_storage_id;
                    $this->position = $nextPos;
                } else {
                    $this->storage_id = null;
                    $this->position = null;
                }
                // Keep sold_storage_id/position for history reference
            }
        } else {
            $this->storage_id = null;
            $this->position = null;
        }
    }

    public function tabItems()
    {
        return $this->belongsTo(TabItem::class, 'id', 'item_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // 1. Determine status based on sold date / explicit status / sale_id
            if (! is_null($item->sold)) {
                $item->status = self::STATUS_SOLD;
            } elseif ($item->status === self::STATUS_SOLD) {
                $item->sold = now();
            } elseif (! is_null($item->sale_id)) {
                $item->status = self::STATUS_RESERVED;
            } else {
                $item->status = self::STATUS_AVAILABLE;
            }

            // Set partially_sold_at when transitioning to STATUS_RESERVED (if not already set)
            if ($item->status === self::STATUS_RESERVED
                && ! is_null($item->sale_id)
                && is_null($item->partially_sold_at)) {
                $item->partially_sold_at = now();
            }

            // 2. If item is not sold, ensure sold date is cleared
            if ($item->status !== self::STATUS_SOLD && ! is_null($item->sold)) {
                $item->sold = null;
            }

            // 3. Ensure sold items don't occupy a physical position
            if ($item->status === self::STATUS_SOLD) {
                // Record last location if not already recorded
                if (! is_null($item->storage_id) && is_null($item->sold_storage_id)) {
                    $item->sold_storage_id = $item->storage_id;
                    $item->sold_position = $item->position;
                    $item->sold_storage_name = $item->storage?->name;
                }
                $item->storage_id = null;
                $item->position = null;
            }
        });

        static::updating(function ($item) {
            $originalStorageId = $item->getOriginal('storage_id');
            // auto-assign only if newly assigned to storage and no explicit position
            if (is_null($originalStorageId)
                && ! is_null($item->storage_id)
                && is_null($item->position)
            ) {
                $item->position = self::getNextAvailablePosition($item->storage_id);
            }
        });

        static::creating(function ($item) {
            // auto-assign only if storage set and no position provided
            if ($item->storage_id && is_null($item->position)) {
                DB::transaction(function () use ($item) {
                    $item->position = self::getNextAvailablePosition($item->storage_id);
                });
            }
        });
    }

    public static function getNextAvailablePosition($storageId)
    {

        // gather occupied positions from saved items and draft items
        $itemPositions = self::where('storage_id', $storageId)
            ->whereNotNull('position')
            ->whereIn('status', [self::STATUS_AVAILABLE, self::STATUS_RESERVED])
            ->whereNull('sold')
            ->pluck('position')
            ->toArray();
        $draftPositions = DraftItem::where('storage_id', $storageId)
            ->whereNotNull('storage_position')
            ->pluck('storage_position')
            ->toArray();
        $occupied = array_unique(array_merge($itemPositions, $draftPositions));
        // find first available starting from 1
        $position = 1;
        while (in_array($position, $occupied)) {
            $position++;
        }

        return $position;
    }

    public function getSoldAttributeFallback($value)
    {
        // Si sold ya tiene valor, retorna ese valor
        if (! is_null($value)) {
            return $value;
        }

        // Si sold es null y hay relación sale, retorna sale->created_at
        return $this->sale ? $this->sale->created_at : null;
    }

    /**
     * Serialize dates to ISO8601 in user's timezone.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        $userTimezone = config('app.user_timezone', config('app.timezone'));
        $date = Carbon::instance($date)
            ->setTimezone($userTimezone)
            ->format('Y-m-d H:i');

        return $date;
    }
}
