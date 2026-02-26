<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashOnHand extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance'];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }
}
