<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\NotifikasiLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotifikasiLog>
 */
class NotifikasiLogFactory extends Factory
{
    protected $model = NotifikasiLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => 'laporan_created',
            'channel' => fake()->randomElement(['telegram', 'whatsapp', 'webpush', 'email']),
            'status' => fake()->randomElement(['sent', 'failed', 'skipped']),
            'error' => null,
            'laporan_id' => Laporan::factory(),
            'sent_at' => now(),
        ];
    }
}

