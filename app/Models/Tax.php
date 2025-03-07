<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
  use HasFactory;

  protected $fillable = ["name", "percentage", "user_id"];

  public function users()
  {
    return $this->hasMany(User::class);
  }
}
