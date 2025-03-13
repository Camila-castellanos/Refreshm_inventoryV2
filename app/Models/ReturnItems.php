<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItems extends Model
{
  use HasFactory;

  protected $fillable = ["item", "customer", "credit", "imei", "model", "sale", "created_at", "updated_at"];
}
