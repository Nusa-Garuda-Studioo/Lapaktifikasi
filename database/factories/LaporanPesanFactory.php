<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\LaporanPesan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanPesan>
 */
class LaporanPesanFactory extends Factory
{
    protected $model = LaporanPesan::class;

    public function definition(): array
    {
        return [
            'laporan_id' => Laporan::factory(),
            'sender_user_id' => User::factory(),
            'sender_role' => fake()->randomElement(['customer', 'seller', 'admin', 'system']),
            'body' => fake()->paragraph(),
            'sumber' => fake()->randomElement(['web', 'telegram', 'system']),
            'lampiran' => null,
            'telegram_message_id' => fake()->numberBetween(1000, 999999),
        ];
    }
}

