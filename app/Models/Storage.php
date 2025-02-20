<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    use HasFactory;

    public $fillable = ['name', 'limit'];

    public function items() {
        return $this->hasMany(Item::class);
    }

    protected static function boot() {
        parent::boot();

        static::deleting(function ($storage) {
            Item::where('storage_id', $storage->id)
                ->update([
                    'position' => null,
                    'absolute_position' => null
                ]);
        });
    }
}
