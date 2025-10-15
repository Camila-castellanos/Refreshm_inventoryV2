<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\DraftItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Scopes\CompanyItemScope;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Item extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        "date", "sale_id", "supplier", "manufacturer", "storage_id", "position",
        "model", "colour", "battery", "grade",
        "issues", "cost", "imei", "selling_price",
        "customer", "sold", "hold", "discount", "tax",
        "subtotal", "profit", 'user_id', 'vendor_id', "custom_values",
        "sold_storage_id", "sold_position", "sold_storage_name", 'shop_id',
        'type'
    ];
    
    // Cast date and sold attributes as full datetime
    protected $casts = [
        'date' => 'datetime',
        'sold' => 'datetime',
    ];

    // Append custom attributes to JSON
    protected $appends = [
        'main_photo_url',
        'main_photo_thumb',
        'photo_urls',
        'photo_count',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyItemScope());
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

    public function storage() {
        return $this->belongsTo(Storage::class);
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
        return $this->getFirstMediaUrl('item-photos', 'preview') 
            ?: asset('images/item-placeholder.svg');
    }

    /**
     * Get the main item photo thumbnail URL
     */
    public function getMainPhotoThumbAttribute(): string
    {
        return $this->getFirstMediaUrl('item-photos', 'thumb') 
            ?: asset('images/item-placeholder.svg');
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
    
    public function removeSale(): bool
    {
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

    public function tabItems()
    {
        return $this->belongsTo(TabItem::class, 'id', 'item_id');
    }

    protected static function boot()
    {
        parent::boot();
    
        static::updating(function ($item) {
            $originalStorageId = $item->getOriginal('storage_id');
            // auto-assign only if newly assigned to storage and no explicit position
            if (is_null($originalStorageId)
                && !is_null($item->storage_id)
                && is_null($item->position)
            ) {
                $item->position = self::getNextAvailablePosition($item->storage_id);
            }
            // if the item is being sold and the sale_id is set, set the sold date
        $originalSaleId = $item->getOriginal('sale_id');
        if (is_null($originalSaleId) && $item->sale_id) {
            $item->sold = now();
        }
        });
        static::creating(function ($item) {
            // auto-assign only if storage set and no position provided
            if ($item->storage_id && is_null($item->position)) {
                DB::transaction(function () use ($item) {
                    $item->position = self::getNextAvailablePosition($item->storage_id);
                });
            }
            // if the item is being sold and the sale_id is set, set the sold date
            if ($item->sale_id && is_null($item->sold)) {
            $item->sold = now();
            }
        });

            

    }

    public static function getNextAvailablePosition($storageId)
{
    
    // gather occupied positions from saved items and draft items
    $itemPositions = self::where('storage_id', $storageId)
        ->whereNotNull('position')
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
    if (!is_null($value)) {
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
