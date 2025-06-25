<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Bill extends Model
{
  use HasFactory;
  protected $fillable = [
    "status",
    "date",
    "vendor",
    "total",
    "amount_paid",
    "balance_remaining",
    "user_id",
    "vendor_id",
    "subtotal",
    "tax",
    "flat_tax",
    "invoice",
    "tax_id"
  ];
  // Cast 'date' attribute as full datetime
  protected $casts = [
    'date' => 'datetime:Y-m-d H:i:s',
  ];

  /**
   * Serialize dates to 'Y-m-d H:i:s' in user's timezone.
   */
  protected function serializeDate(\DateTimeInterface $date): string
  {
    $userTimezone = config('app.user_timezone', config('app.timezone'));
    return Carbon::instance($date)
      ->setTimezone($userTimezone)
      ->format('Y-m-d H:i:s');
  }
}
