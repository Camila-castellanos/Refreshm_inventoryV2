<?php

namespace App\Models;

use Database\Factories\LoginActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class LoginActivity extends Model
{
    use HasFactory;

    protected static function newFactory(): LoginActivityFactory
    {
        return LoginActivityFactory::new();
    }

    protected $fillable = [
        'user_id',
        'login_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    public function getIpAddressAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return $this->normalizeIp(Crypt::decryptString($value));
        } catch (\Throwable $e) {
            try {
                return $this->normalizeIp(Crypt::decrypt($value));
            } catch (\Throwable $e) {
                return $this->normalizeIp($value);
            }
        }
    }

    public function setIpAddressAttribute($value): void
    {
        $this->attributes['ip_address'] = empty($value)
            ? $value
            : Crypt::encryptString($this->normalizeIp($value));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    private function normalizeIp(string $value): string
    {
        $unserialized = @unserialize($value);

        if ($unserialized !== false && is_string($unserialized)) {
            return $unserialized;
        }

        if ($value === 'b:0;') {
            return '0';
        }

        return $value;
    }
}
