<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'amount_paid' => $this->faker->randomFloat(2, 10, 500),
            'balance_remaining' => $this->faker->randomFloat(2, 0, 100),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'transfer']),
            'payment_account' => $this->faker->randomElement(['Cash on Hand', 'Bank Account', 'Petty Cash']),
            'payment_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function forSale(Sale $sale): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_id' => $sale->id,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
        ]);
    }

    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
            'payment_account' => 'Bank Account',
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_remaining' => 50.00,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_remaining' => 0,
        ]);
    }
}
