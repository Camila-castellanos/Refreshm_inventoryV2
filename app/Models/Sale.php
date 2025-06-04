<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    
}
