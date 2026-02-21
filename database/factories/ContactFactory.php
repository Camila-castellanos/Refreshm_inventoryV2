<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'user_id' => User::factory(),
            'type' => 'custom',
            'prospect_id' => null,
            'customer_id' => null,
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
            'customer_id' => null,
        ]);
    }

    public function prospect(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'prospect',
            'prospect_id' => null,
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'custom',
        ]);
    }
}
