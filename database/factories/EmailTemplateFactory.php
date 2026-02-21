<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
        ]);
    }
}
