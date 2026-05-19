<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\IncomingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomingRequestFactory extends Factory
{
    protected $model = IncomingRequest::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'store' => fake()->company(),
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
            'processed' => false,
            'shipping' => null,
            'customer_id' => Customer::factory(),
        ];
    }

    public function forCustomer(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer?->id ?? Customer::factory(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => false,
        ]);
    }
}