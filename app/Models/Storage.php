<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\UserStorageScope;

class Storage extends Model
{
    use HasFactory;

    public $fillable = ['name', 'limit', 'user_id'];

    public function items() {
        return $this->hasMany(Item::class);
    }

    protected static function boot() {
        parent::boot();

        static::deleting(function ($storage) {
            Item::where('storage_id', $storage->id)
                ->update([
                    'position' => null,
                ]);
        });
    }

    protected static function booted()
    {
        static::addGlobalScope(new UserStorageScope());
    }

    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}
}
