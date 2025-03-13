<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
  use HasFactory;
  protected $fillable = [
    "date",
    "name",
    "category",
    "amount",
    "tax",
    "tax_id",
    "total",
    "user_id",
  ];
}
