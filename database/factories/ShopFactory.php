<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'company_id' => Company::factory(),
            'address' => fake()->address(),
            'public_tabs' => [],
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $owner?->company_id ?? 1,
        ]);
    }

    public function withPublicTabs(array $tabs = ['iPhone', 'Samsung', 'Accessories']): static
    {
        return $this->state(fn (array $attributes) => [
            'public_tabs' => $tabs,
        ]);
    }
}
