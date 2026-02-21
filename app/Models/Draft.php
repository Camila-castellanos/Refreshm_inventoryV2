<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'vendor', 'date'];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(DraftItem::class);
    }
}
