<?php

namespace Database\Factories;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProspectFactory extends Factory
{
    protected $model = Prospect::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => fake()->company(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Canada',
            'address' => fake()->address(),
            'phone_number' => fake()->phoneNumber(),
            'contact_type' => 'lead',
            'user_id' => User::factory(),
            'company_id' => 1,
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
            'company_id' => $owner?->company_id ?? 1,
        ]);
    }
}
