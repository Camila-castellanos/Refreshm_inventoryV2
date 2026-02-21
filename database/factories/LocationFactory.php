<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Main Floor', 'Basement', 'Warehouse', 'Display Area', 'Repair Station']),
            'address' => fake()->address(),
            'store_id' => 1,
        ];
    }

    public function forStore(Shop $shop): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $shop->id,
        ]);
    }
}
