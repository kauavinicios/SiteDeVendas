<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Customer->value,
            'active' => true,
            'phone' => null,
            'marketing_opt_in' => false,
            'marketing_opt_in_at' => null,
            'marketing_opt_out_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin->value]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => UserRole::Manager->value]);
    }

    public function storekeeper(): static
    {
        return $this->state(fn () => ['role' => UserRole::Storekeeper->value]);
    }

    public function support(): static
    {
        return $this->state(fn () => ['role' => UserRole::Support->value]);
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role' => UserRole::Customer->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function subscribedToMarketing(): static
    {
        return $this->state(fn () => [
            'marketing_opt_in' => true,
            'marketing_opt_in_at' => now(),
            'marketing_opt_out_at' => null,
        ]);
    }
}
