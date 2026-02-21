<?php

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'text' => fake()->word(),
            'type' => fake()->randomElement(['text', 'number', 'date', 'select']),
            'value' => fake()->word(),
            'active' => true,
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }
}
