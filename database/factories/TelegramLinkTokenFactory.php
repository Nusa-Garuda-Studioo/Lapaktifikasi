<?php

namespace Database\Factories;

use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TelegramLinkToken>
 */
class TelegramLinkTokenFactory extends Factory
{
    protected $model = TelegramLinkToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(32)),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
        ];
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}

