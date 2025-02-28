<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ["user_id", "name", "internal_number", "store_margin", "items"];

    protected $casts = [
        "items" => "array",
    ];

    /**
     * An User may have many quotes
     * @return Collection
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total amount of a Quote
     * @return number
     */
    public function getTotalAttribute()
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item["value"];
        }

        return $total;
    }
}
