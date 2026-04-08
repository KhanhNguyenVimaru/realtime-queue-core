<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titles = [
            'Frontend Workshop',
            'Backend Bootcamp',
            'Realtime Systems 101',
            'Laravel Best Practices',
            'Nuxt UI Showcase',
            'Queue Deep Dive',
            'Database Tuning Night',
            'DevOps for Builders',
            'Testing Strategy Meetup',
            'Scaling API Design',
        ];

        for ($i = 0; $i < 30; $i++) {
            $start = Carbon::now()->addDays(random_int(1, 30))->setTime(random_int(8, 18), [0, 15, 30, 45][random_int(0, 3)]);
            $end = (clone $start)->addHours(random_int(1, 4));

            Event::create([
                'host_id' => 1,
                'title' => Arr::random($titles) . ' #' . ($i + 1),
                'description' => fake()->sentence(12),
                'img' => null,
                'limit' => random_int(0, 4) === 0 ? null : random_int(20, 120),
                'starts_at' => $start,
                'ends_at' => $end,
            ]);
        }
    }
}
