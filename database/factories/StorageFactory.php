<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageFactory extends Factory
{
    protected $model = Storage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Main Storage', 'Warehouse A', 'Display Area', 'Back Room', 'Repair Station']),
            'limit' => fake()->randomElement([50, 100, 200, 500]),
            'company_id' => null,
            'priority' => 1,
            'is_default' => false,
        ];
    }

    public function forCompany(?Company $company = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company?->id ?? Company::factory(),
        ]);
    }

    public function withLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'limit' => $limit,
        ]);
    }

    public function asDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'priority' => 0,
        ]);
    }

    public function withPriority(int $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }
}
