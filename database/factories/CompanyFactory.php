<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'owner_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Company $company) {
            if (! $company->owner_id) {
                $owner = User::factory()->create([
                    'company_id' => $company->id,
                    'role' => 'OWNER',
                ]);
                $company->owner_id = $owner->id;
                $company->save();
            }
        });
    }

    public function forOwner(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }

    public function withOwner(?User $owner = null): static
    {
        return $this->afterCreating(function (Company $company) use ($owner) {
            $user = $owner ?? User::factory()->create([
                'company_id' => $company->id,
                'role' => 'OWNER',
            ]);
            $company->owner_id = $user->id;
            $company->save();
        });
    }

    public function owner(): static
    {
        return $this->withOwner();
    }
}
