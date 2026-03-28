<?php

namespace Database\Factories;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoginActivityFactory extends Factory
{
    protected $model = LoginActivity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'login_at' => now(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function withIp(string $ip): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ip,
        ]);
    }

    public function withUserAgent(string $userAgent): static
    {
        return $this->state(fn (array $attributes) => [
            'user_agent' => $userAgent,
        ]);
    }
}
