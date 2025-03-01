<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{

    protected $fillable = [
       "subject", "content", "user_id"
    ];

    use HasFactory;
}
