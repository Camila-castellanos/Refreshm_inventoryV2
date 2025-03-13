<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentsBills extends Model
{
  use HasFactory;

  protected $fillable = ["bill_id", "amount_paid", "balance_remaining", "payment_method", "payment_account", "payment_date"];
}
