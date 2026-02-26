<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'first_name', 'last_name', 'company_name', 'city', 'state', 'country', 'address', 'phone_number', 'contact_type', 'user_id', 'company_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
