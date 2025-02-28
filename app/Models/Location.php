<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ["name", "address", "store_id"];

    /**
      A Location belongs to a Store
    */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * A Store can have many Users
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

}
