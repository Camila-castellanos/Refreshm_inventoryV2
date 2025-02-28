<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Issue extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ["name"];

    /**
     * Many to Many relationship with Devices
     * @return Collection
     */
    public function devices()
    {
        return $this->belongsToMany(Device::class)
            ->withPivot("deduction");
    }


    /**
     * Many to Many relationship with Quotes
     * @return Collection
     */
    public function quotes()
    {
        return $this->belongsToMany(Quote::class)->withPivot("deduction");
    }
}
