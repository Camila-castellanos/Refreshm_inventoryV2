<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyUsersSharedScope;
class EmailTemplate extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }

    protected $fillable = [
       "subject", "content", "user_id"
    ];

    use HasFactory;
}
