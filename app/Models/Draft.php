<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyUsersSharedScope;
class Draft extends Model
{
     protected $fillable = ['user_id','title', 'vendor', 'date', 'items'];

      protected $casts = [
        'date' => 'date',
        'items' => 'array',
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
