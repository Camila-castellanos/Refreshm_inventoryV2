<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{

  protected $fillable = ["email", "first_name", "last_name", "company_name", "city", "state", "country", "address", "phone_number", "contact_type", "user_id"];

  use HasFactory;
}
