<?php

namespace Database\Seeders;

use App\Models\WorkingHour;
use Illuminate\Database\Seeder;

class WorkingHourSeeder extends Seeder
{
    public function run(): void
    {
        // Modifica orele de aici (valori provizorii)
        $weekday = ['17:30', '20:00'];
        $weekend = ['09:00', '18:00'];

        foreach (range(1, 7) as $day) {
            [$start, $end] = $day <= 5 ? $weekday : $weekend;

            WorkingHour::updateOrCreate(
                ['day_of_week' => $day],
                ['start_time' => $start, 'end_time' => $end, 'is_active' => true]
            );
        }
    }
}