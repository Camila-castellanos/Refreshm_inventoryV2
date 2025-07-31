<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\CompanyStorageScope;

class Storage extends Model
{
    use HasFactory;

    public $fillable = ['name', 'limit', 'company_id'];

    public function items() {
        return $this->hasMany(Item::class);
    }

    public function draftItems() {
        return $this->hasMany(DraftItem::class);
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
        static::addGlobalScope(new CompanyStorageScope());
    }

    /**
     * Define the relationship: A Storage BELONGS TO one Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
