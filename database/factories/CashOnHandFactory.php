<?php

namespace Database\Factories;

use App\Models\CashOnHand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashOnHandFactory extends Factory
{
    protected $model = CashOnHand::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => fake()->randomFloat(2, 100, 10000),
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => is_object($owner) ? $owner->id : ($owner ?? User::factory()),
        ]);
    }

    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }
}
