<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftItem extends Model
{
    use HasFactory;

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
        'draft_unassigned',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'draft_unassigned' => 'boolean',
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
