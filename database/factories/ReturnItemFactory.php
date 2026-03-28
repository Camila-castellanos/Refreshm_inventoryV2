<?php

namespace Database\Factories;

use App\Models\ReturnItems;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnItemFactory extends Factory
{
    protected $model = ReturnItems::class;

    public function definition(): array
    {
        return [
            'item' => (string) $this->faker->numberBetween(1, 100),
            'customer' => (string) $this->faker->numberBetween(1, 50),
            'credit' => $this->faker->randomFloat(2, 0, 1000),
            'imei' => $this->faker->numerify('###############'),
            'model' => $this->faker->randomElement(['iPhone 15', 'Galaxy S24', 'Pixel 8']),
            'sale' => (string) $this->faker->numberBetween(1, 50),
            'requested' => false,
        ];
    }

    public function withCredit(float $credit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit' => $credit,
        ]);
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'requested' => true,
        ]);
    }
}
