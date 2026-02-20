<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer' => fake()->name(),
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'phone_optional' => null,
            'account_number' => fake()->unique()->numerify('ACC-#####'),
            'website' => fake()->optional()->domainName(),
            'notes' => fake()->optional()->sentence(),
            'currency' => 'CAD',
            'billing_address' => fake()->address(),
            'billing_address_country' => 'Canada',
            'billing_address_state' => fake()->state(),
            'billing_address_city' => fake()->city(),
            'billing_address_postal' => fake()->postcode(),
            'credit' => 0,
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company?->id ?? Company::factory(),
        ]);
    }

    public function withCredit(float $credit): static
    {
        return $this->state(fn (array $attributes) => [
            'credit' => $credit,
        ]);
    }
}
