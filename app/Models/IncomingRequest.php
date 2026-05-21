<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);

        static::deleting(function ($request) {
            $request->items()->delete();
        });
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
        'customer_id',
    ];

    protected $casts = [
        'shipping' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(IncomingRequestItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
