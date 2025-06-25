<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
