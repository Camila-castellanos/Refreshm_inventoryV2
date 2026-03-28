<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Tab;
use App\Models\TabHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TabHistoryFactory extends Factory
{
    protected $model = TabHistory::class;

    public function definition(): array
    {
        return [
            'tab_id' => Tab::factory(),
            'item_id' => Item::factory(),
        ];
    }

    public function forTab($tab): static
    {
        return $this->state(fn (array $attributes) => [
            'tab_id' => $tab->id ?? $tab,
        ]);
    }

    public function forItem($item): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $item->id ?? $item,
        ]);
    }
}
