<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'date' => now()->format('Y-m-d'),
            'name' => fake()->sentence(3),
            'category' => fake()->randomElement(['Supplies', 'Utilities', 'Rent', 'Maintenance', 'Other']),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'tax' => 0,
            'tax_id' => null,
            'subtotal' => fake()->randomFloat(2, 10, 1000),
            'total' => fake()->randomFloat(2, 10, 1000),
            'user_id' => User::factory(),
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => is_object($owner) ? $owner->id : ($owner ?? User::factory()),
        ]);
    }

    public function withTax(float $taxRate = 13): static
    {
        return $this->state(fn (array $attributes) => [
            'tax' => $taxRate,
        ]);
    }

    public function withCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
