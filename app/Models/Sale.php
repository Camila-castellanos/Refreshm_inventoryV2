<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Scopes\CompanyUsersSharedScope;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id", 
        "subtotal", 
        "discount", 
        "flatTax", 
        "tax", 
        "total", 
        "amount_paid", 
        "balance_remaining", 
        "paid", 
        "payment_method" 
        ,"payment_account", 
        "notes", 
        "credit", 
        "tax_id", 
        "date",
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyUsersSharedScope);
    }

    // Cast `date` attribute as full datetime
    protected $casts = [
        'date' => 'datetime',
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
