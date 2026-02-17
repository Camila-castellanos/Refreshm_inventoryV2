<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'market_id' => null,
            'subtotal' => fake()->randomFloat(2, 50, 1000),
            'discount' => fake()->optional(0.3)->randomFloat(2, 0, 50),
            'flatTax' => fake()->randomFloat(2, 5, 100),
            'tax' => 13.00,
            'total' => fake()->randomFloat(2, 100, 1100),
            'amount_paid' => 0,
            'balance_remaining' => fake()->randomFloat(2, 0, 1000),
            'paid' => 1,
            'payment_method' => fake()->randomElement(['cash', 'card', 'transfer']),
            'channel' => 'system',
            'notes' => fake()->optional()->sentence(),
            'credit' => 0,
            'tax_id' => null,
            'date' => now(),
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $attributes['total'],
            'balance_remaining' => 0,
            'paid' => 2,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => 0,
            'balance_remaining' => $attributes['total'],
            'paid' => 1,
        ]);
    }

    public function withTax(float $tax): static
    {
        return $this->state(fn (array $attributes) => [
            'tax' => $tax,
        ]);
    }

    public function withCredit(float $credit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit' => $credit,
        ]);
    }
}
