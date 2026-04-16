<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingRequestAppendAttempt extends Model
{
    use HasFactory;

    protected $table = 'incoming_request_append_attempts';

    protected $fillable = [
        'incoming_request_id',
        'sale_id',
        'idempotency_key',
        'user_id',
        'state_transition',
        'appended_count',
        'skipped_count',
        'response_payload',
    ];

    protected $casts = [
        'response_payload' => 'array',
    ];
}
