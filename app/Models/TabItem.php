<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tab;

class TabItem extends Model
{
    use HasFactory;

    protected $fillable = [
        "tab_id", "item_id"
    ];

    public static function getTab($tabId)
    {
        return Tab::find($tabId);
    }
}
