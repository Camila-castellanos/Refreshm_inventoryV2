<?php

namespace Database\Factories;

use App\Models\MailList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailListFactory extends Factory
{
    protected $model = MailList::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(2),
            'names' => json_encode([fake()->name(), fake()->name()]),
            'emails' => json_encode([fake()->safeEmail(), fake()->safeEmail()]),
            'company_id' => null,
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
        ]);
    }
}
