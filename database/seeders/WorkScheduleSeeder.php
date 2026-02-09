<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkSchedule;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];

        foreach ($days as $day) {
            WorkSchedule::create([
                'day_name' => $day,
                'start_time' => '07:00:00', // Masuk jam 7
                'end_time' => '15:00:00',   // Pulang jam 3 sore
                'late_tolerance' => 15,     // Toleransi 15 menit
                'is_holiday' => false,
            ]);
        }

        // Sabtu Minggu Libur
        WorkSchedule::create(['day_name' => 'sabtu', 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'is_holiday' => true]);
        WorkSchedule::create(['day_name' => 'minggu', 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'is_holiday' => true]);
    }
}
