<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
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
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'profile_picture' => 'default.png',
            'role_id' => 2,
            'remember_token' => Str::random(10),
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_linked_at' => null,
            'telegram_blocked_at' => null,
            'notif_channel_laporan' => 'auto',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 1,
        ]);
    }

    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 3,
        ]);
    }

    public function withTelegram(?int $chatId = null, ?string $username = null): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_chat_id' => $chatId ?? fake()->numberBetween(100000000, 999999999),
            'telegram_username' => $username ?? fake()->userName(),
            'telegram_linked_at' => now(),
            'telegram_blocked_at' => null,
        ]);
    }

    public function blockedTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_chat_id' => fake()->numberBetween(100000000, 999999999),
            'telegram_blocked_at' => now(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
