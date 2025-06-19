<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
     protected $fillable = ['user_id','title', 'vendor', 'date', 'items'];

      protected $casts = [
        'date' => 'date',
        'items' => 'array',
    ];

    public function user()
    {
      return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(DraftItem::class);
    }
}
