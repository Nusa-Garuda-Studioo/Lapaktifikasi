<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laporan>
 */
class LaporanFactory extends Factory
{
    protected $model = Laporan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'assigned_to' => null,
            'judul' => fake()->sentence(4),
            'deskripsi' => fake()->paragraph(),
            'gambar' => null,
            'status' => 'pending',
            'first_response_at' => null,
            'sla_due_at' => now()->addHours(24),
            'escalated_at' => null,
            'telegram_topic_id' => null,
        ];
    }
}

