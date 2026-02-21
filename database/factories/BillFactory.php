<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        return [
            'status' => 0,
            'date' => now()->format('Y-m-d'),
            'vendor' => fake()->company(),
            'total' => fake()->randomFloat(2, 100, 5000),
            'amount_paid' => 0,
            'balance_remaining' => fake()->randomFloat(2, 100, 5000),
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'subtotal' => fake()->randomFloat(2, 100, 5000),
            'tax' => 0,
            'flat_tax' => 0,
            'invoice' => fake()->unique()->numerify('INV-#####'),
            'tax_id' => null,
        ];
    }

    public function forOwner($owner = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner?->id ?? User::factory(),
            'vendor_id' => $owner ? Vendor::factory()->forOwner($owner) : Vendor::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
            'amount_paid' => $attributes['total'] ?? fake()->randomFloat(2, 100, 5000),
            'balance_remaining' => 0,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
            'amount_paid' => 0,
            'balance_remaining' => $attributes['total'] ?? fake()->randomFloat(2, 100, 5000),
        ]);
    }

    public function withTax(float $taxRate = 13): static
    {
        return $this->state(fn (array $attributes) => [
            'tax' => $taxRate,
            'flat_tax' => (($attributes['subtotal'] ?? 100) * $taxRate / 100),
        ]);
    }

    public function withInvoice(?string $invoice = null): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice' => $invoice ?? fake()->unique()->numerify('INV-#####'),
        ]);
    }
}
