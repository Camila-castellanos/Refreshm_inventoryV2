<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncomingRequest extends Model
{
    use HasFactory;

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
