<?php

namespace App\Models\Ecommerce;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'market_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'market_id',
        'item_id',
        'custom_price',
        'is_visible',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'custom_price' => 'decimal:2',
        'is_visible' => 'boolean',
    ];

    /**
     * Relationship: Market
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Relationship: Item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the price for this market item
     * Returns custom price if set, otherwise fallback to item's selling_price
     */
    public function getPrice(): float
    {
        return $this->custom_price !== null ? (float) $this->custom_price : (float) $this->item->selling_price;
    }

    /**
     * Set a custom price for this market item
     */
    public function setPrice(float $price): void
    {
        $this->custom_price = $price;
        $this->save();
    }

    /**
     * Reset to default price (selling_price from item)
     */
    public function resetPrice(): void
    {
        $this->custom_price = null;
        $this->save();
    }

    /**
     * Get the visibility status
     */
    public function isVisible(): bool
    {
        return $this->is_visible;
    }

    /**
     * Toggle visibility
     */
    public function toggleVisibility(): bool
    {
        $this->is_visible = !$this->is_visible;
        $this->save();
        return $this->is_visible;
    }

    /**
     * Set visibility status
     */
    public function setVisibility(bool $isVisible): void
    {
        $this->is_visible = $isVisible;
        $this->save();
    }

    /**
     * Scope: Visible items only
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Hidden items only
     */
    public function scopeHidden($query)
    {
        return $query->where('is_visible', false);
    }

    /**
     * Scope: Items with custom prices
     */
    public function scopeWithCustomPrice($query)
    {
        return $query->whereNotNull('custom_price');
    }

    /**
     * Scope: Items without custom prices
     */
    public function scopeWithoutCustomPrice($query)
    {
        return $query->whereNull('custom_price');
    }

    /**
     * Scope: Filter by market
     */
    public function scopeForMarket($query, $marketId)
    {
        return $query->where('market_id', $marketId);
    }

    /**
     * Scope: Filter by item
     */
    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }
}
