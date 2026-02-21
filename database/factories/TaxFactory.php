<?php

namespace Database\Factories;

use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['HST', 'GST', 'PST', 'VAT', 'Sales Tax']),
            'percentage' => fake()->randomElement([5, 7, 13, 15, 20]),
            'user_id' => User::factory(),
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
        ]);
    }

    public function withPercentage(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => $percentage,
        ]);
    }
}
