<?php

namespace Database\Factories;

use App\Models\TelegramSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramSession>
 */
class TelegramSessionFactory extends Factory
{
    protected $model = TelegramSession::class;

    public function definition(): array
    {
        return [
            'chat_id' => fake()->numberBetween(100000000, 999999999),
            'flow' => 'laporan_create',
            'step' => 'awaiting_description',
            'payload' => ['order_id' => fake()->uuid()],
            'expires_at' => now()->addMinutes(30),
        ];
    }
}

