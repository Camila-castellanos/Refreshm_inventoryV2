<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncomingRequest extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }

    protected $table = 'incoming_requests';

    protected $fillable = [
        'name',
        'email',
        'store',
        'notes',
        'user_id',
        'processed',
        'shipping',
    ];

    protected $casts = [
        'shipping' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(IncomingRequestItem::class);
    }
}
