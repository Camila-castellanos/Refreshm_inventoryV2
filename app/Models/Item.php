<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\DraftItem;


class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        "date", "sale_id", "supplier", "manufacturer", "storage_id", "position",
        "model", "colour", "battery", "grade",
        "issues", "cost", "imei", "selling_price",
        "customer", "sold", "hold", "discount", "tax",
        "subtotal", "profit", 'user_id', 'vendor_id', "custom_values",
        "sold_storage_id", "sold_position", "sold_storage_name", 'shop_id',
        'type'
    ];
    
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
        ->whereNotNull('position')
        ->pluck('position')
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

    
}
