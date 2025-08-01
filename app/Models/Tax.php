<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\CompanyUsersSharedScope;

class Tax extends Model
{
  use HasFactory;

  protected $fillable = ["name", "percentage", "user_id"];

  protected static function booted()
  {
      static::addGlobalScope(new CompanyUsersSharedScope);
  }

  public function users()
  {
    return $this->hasMany(User::class);
  }
}
