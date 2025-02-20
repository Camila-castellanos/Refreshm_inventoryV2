<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;


class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        "date", "sale_id", "supplier", "manufacturer", "storage_id",
        "model", "colour", "battery", "grade",
        "issues", "cost", "imei", "selling_price",
        "customer", "sold", "hold", "discount", "tax",
        "subtotal", "profit", 'user_id', 'vendor_id', "custom_values",
        "sold_storage_id", "sold_position", "sold_storage_name"
    ];

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
        
            if (is_null($originalStorageId) && !is_null($item->storage_id)) {
                $item->position = self::getNextAvailablePosition($item->storage_id);
            }
        });
        static::creating(function ($item) {
            if ($item->storage_id) {
                DB::transaction(function () use ($item) {
                    $item->position = self::getNextAvailablePosition($item->storage_id);
                });
            }
        });

            

    }

    public static function getNextAvailablePosition($storageId)
{
    
    $occupiedPositions = Item::where('storage_id', $storageId)
        ->orderBy('position', 'asc')
        ->whereNotNull('position')
        ->pluck('position')
        ->toArray();

 
    $position = 1;
    while (in_array($position, $occupiedPositions)) {
        $position++;
    }

    return $position; 


        foreach ($occupiedPositions as $occupied) {
        if ($position < $occupied) {
            return $position; // Retorna la posición disponible más cercana a 0.
        }
        $position++;
    }
}

    
}
