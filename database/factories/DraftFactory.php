<?php

namespace Database\Factories;

use App\Models\Draft;
use App\Models\DraftItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DraftFactory extends Factory
{
    protected $model = Draft::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'vendor' => fake()->optional(0.7)->company(),
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function withItems(int $count = 3): static
    {
        return $this->has(
            DraftItem::factory()->count($count),
            'items'
        );
    }

    public function forOwner(int $ownerId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $ownerId,
        ]);
    }
}
