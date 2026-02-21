<?php

namespace Database\Factories;

use App\Models\DraftItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class DraftItemFactory extends Factory
{
    protected $model = DraftItem::class;

    public function definition(): array
    {
        $manufacturers = ['Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi'];

        return [
            'vendor_id' => null,
            'tax_id' => null,
            'storage_id' => null,
            'manufacturer' => fake()->randomElement($manufacturers),
            'model' => fake()->word().' '.fake()->randomNumber(3),
            'type' => 'device',
            'colour' => fake()->randomElement(['Black', 'White', 'Blue', 'Red', 'Green']),
            'battery' => fake()->randomElement(['90%', '85%', '80%', '95%']),
            'grade' => fake()->randomElement(['A', 'B', 'C']),
            'issues' => null,
            'imei' => fake()->unique()->numerify('###############'),
            'location' => null,
            'storage_position' => null,
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
            'subtotal' => null,
            'cost' => fake()->randomFloat(2, 50, 500),
            'selling_price' => fake()->randomFloat(2, 100, 800),
            'draft_unassigned' => false,
        ];
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_id' => null,
            'storage_position' => null,
            'location' => null,
            'draft_unassigned' => true,
        ]);
    }

    public function forDraft(int $draftId): static
    {
        return $this->state(fn (array $attributes) => [
            'draft_id' => $draftId,
        ]);
    }
}
