<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = ["name", "address", "email", "header", "footer", "logo", "price_percent"];

    /**
     * A Store can have many Users
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * A Store can have many locations
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
    * Many to Many relationship with DeviceStorePrice, used to store custom prices
    * @return Illuminate\Support\Collection
    */
    public function customPrices()
    {
        return $this->hasMany(DeviceStorePrice::class);
    }
}
