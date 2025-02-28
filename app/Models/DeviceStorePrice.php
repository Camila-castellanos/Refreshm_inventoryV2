<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStorePrice extends Model
{
    use HasFactory;

    protected $fillable = ["store_id", "device_id", "custom_price"];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
