<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\TelegramMessageMap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramMessageMap>
 */
class TelegramMessageMapFactory extends Factory
{
    protected $model = TelegramMessageMap::class;

    public function definition(): array
    {
        return [
            'chat_id' => fake()->numberBetween(100000000, 999999999),
            'telegram_message_id' => fake()->unique()->numberBetween(1000, 999999),
            'laporan_id' => Laporan::factory(),
            'user_id' => User::factory(),
        ];
    }
}

