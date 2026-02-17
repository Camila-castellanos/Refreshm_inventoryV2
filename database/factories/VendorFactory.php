<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'vendor' => fake()->company(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'phone_optional' => null,
            'website' => fake()->optional()->domainName(),
            'notes' => fake()->optional()->sentence(),
            'currency' => 'CAD',
            'address' => fake()->address(),
            'address_optional' => null,
            'address_country' => 'Canada',
            'address_state' => fake()->state(),
            'address_city' => fake()->city(),
            'address_postal' => fake()->postcode(),
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function forCompany(int $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->create(['company_id' => $companyId])->id,
        ]);
    }

    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
}
