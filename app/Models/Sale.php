<?php

namespace App\Models;

use App\Models\Scopes\CompanyUsersSharedScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'market_id',
        'subtotal',
        'discount',
        'flatTax',
        'tax',
        'total',
        'amount_paid',
        'balance_remaining',
        'paid',
        'payment_method',
        'payment_intent_id',
        'channel',
        'payment_account',
        'notes',
        'extra',
        'customer',
        'credit',
        'tax_id',
        'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);

        // Exclude ecommerce sales by default (show only system and null channel)
        static::addGlobalScope('exclude_ecommerce', function ($query) {
            $query->where(function ($q) {
                $q->where('channel', 'system')
                    ->orWhereNull('channel');
            });
        });
    }

    // Cast `date` attribute as full datetime
    protected $casts = [
        'date' => 'datetime',
        'extra' => 'array',
    ];

    /**
     * Serialize dates to ISO8601 in user's timezone.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        $userTimezone = config('app.user_timezone', config('app.timezone'));

        return Carbon::instance($date)
            ->setTimezone($userTimezone)
            ->format('Y-m-d H:i');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
