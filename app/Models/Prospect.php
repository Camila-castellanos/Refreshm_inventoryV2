<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\CompanyScope;
class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        "email", "first_name", "last_name", "company_name", "city", "state", "country", "address", "phone_number", "contact_type", "user_id", "company_id"
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
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
