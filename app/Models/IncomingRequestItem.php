<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingRequestItem extends Model
{
    use HasFactory;

    protected $table = 'incoming_request_items';

    protected $fillable = [
        'incoming_request_id', 'original_item_id', 'date', 'supplier', 'manufacturer',
        'storage_id', 'position', 'model', 'colour', 'battery', 'grade', 'issues', 'cost', 'imei',
        'selling_price', 'customer', 'user_id', 'vendor_id', 'shop_id', 'type', 'currency'
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(IncomingRequest::class, 'incoming_request_id');
    }

    public function originalItem()
    {
        return $this->belongsTo(Item::class, 'original_item_id');
    }
}
