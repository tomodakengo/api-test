<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt(fake()->password(8)),
            'remember_token' => Str::random(10),
        ];
    }

    public function withPassword(string $password)
    {
        return $this->state(function (array $attributes) use ($password) {
            return [
                'password' => bcrypt($password),
            ];
        });
    }
}
