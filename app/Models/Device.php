<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Device extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "model", "brand", "image", "base_price", "excellent_factor",
        "good_factor", "acceptable_factor", "broken_factor",
        "supplier", "manufacturer"
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['custom_price', "store_price"];


    /**
     * Get the Auth user's store's custom price.
     * @return float
     */
    public function getCustomPriceAttribute()
    {
        $user = Auth::user();
        if ($user && $user->store_id) {
            $deviceStore = $this->customPrices()->where("store_id", $user->store_id)->first();
            if ($deviceStore) {
                return $deviceStore->custom_price;
            }
        }
        return null;
    }

    /**
     * Get the Auth user's store's percent price.
     * @return float
     */
    public function getStorePriceAttribute()
    {
        $user = Auth::user();
        if ($user && $user->store_id) {
            $store = $user->store;
            $storePercent = $store->price_percent;

            return round($this->base_price * (1 - $storePercent / 100));
        }
        return $this->base_price;
    }


    /**
     * Many to Many relationship with Issues
     * @return Illuminate\Support\Collection
     */
    public function issues()
    {
        return $this->belongsToMany(Issue::class)
            ->withPivot("deduction");
    }

    /**
     * Many to Many relationship with DeviceStorePrice, used to save custom prices
     * @return Illuminate\Support\Collection
     */
    public function customPrices()
    {
        return $this->hasMany(DeviceStorePrice::class);
    }
}
