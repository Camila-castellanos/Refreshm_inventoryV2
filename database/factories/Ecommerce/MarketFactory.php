<?php

namespace Database\Factories\Ecommerce;

use App\Models\Ecommerce\Market;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketFactory extends Factory
{
    protected $model = Market::class;

    public function definition(): array
    {
        $name = fake()->company().' Store';

        return [
            'shop_id' => Shop::factory(),
            'name' => $name,
            'slug' => fake()->unique()->slug(),
            'custom_domain' => null,
            'description' => fake()->optional(0.7)->paragraph(),
            'tagline' => fake()->optional(0.5)->sentence(),
            'logo_url' => null,
            'theme_colors' => null,
            'is_active' => true,
            'show_inventory_count' => false,
            'currency' => 'USD',
            'tax_rate' => 0.0,
            'meta_title' => $name,
            'meta_description' => fake()->optional(0.5)->sentence(),
            'meta_keywords' => null,
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->optional(0.7)->phoneNumber(),
            'address' => fake()->optional(0.5)->address(),
            'return_policy' => null,
            'shipping_policy' => null,
            'privacy_policy' => null,
            'faq' => ['title' => 'FAQ', 'description' => '', 'questions' => []],
        ];
    }

    public function forShop($shopId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shop_id' => $shopId?->id ?? $shopId ?? Shop::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
