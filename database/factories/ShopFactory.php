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
            'company_id' => null,
            'address' => fake()->address(),
            'public_tabs' => [],
        ];
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company?->id ?? Company::factory(),
        ]);
    }

    public function withPublicTabs(array $tabs = ['iPhone', 'Samsung', 'Accessories']): static
    {
        return $this->state(fn (array $attributes) => [
            'public_tabs' => $tabs,
        ]);
    }
}
