<?php

namespace Database\Factories;

use App\Models\Tab;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TabFactory extends Factory
{
    protected $model = Tab::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->randomElement(['iPhone', 'Samsung', 'Google', 'Accessories', 'Repair Parts', 'Display', 'Battery']),
            'order' => 1,
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }
}
