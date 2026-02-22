<?php

namespace Database\Factories\Ecommerce;

use App\Models\Ecommerce\Market;
use App\Models\Ecommerce\MarketItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketItemFactory extends Factory
{
    protected $model = MarketItem::class;

    public function definition(): array
    {
        return [
            'market_id' => Market::factory(),
            'item_id' => Item::factory(),
            'custom_price' => null,
            'description' => null,
            'is_visible' => true,
        ];
    }

    public function forMarket($marketId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'market_id' => $marketId ?? Market::factory(),
        ]);
    }

    public function forItem($itemId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $itemId ?? Item::factory(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }

    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => true,
        ]);
    }

    public function withCustomPrice(?float $price = null): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_price' => $price ?? fake()->randomFloat(2, 100, 1000),
        ]);
    }
}
