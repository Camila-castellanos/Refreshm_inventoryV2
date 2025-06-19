<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Draft;
use App\Models\Vendor;
use App\Models\Tax;
use App\Models\Storage;

class DraftItem extends Model
{
    protected $fillable = [
        'draft_id',
        'vendor_id',
        'tax_id',
        'storage_id',
        'manufacturer',
        'model',
        'type',
        'colour',
        'battery',
        'grade',
        'issues',
        'imei',
        'location',
        'storage_position',
        'date',
        'subtotal',
        'cost',
        'selling_price',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}
