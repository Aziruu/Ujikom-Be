<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // Data Dasar & Akun
            UserSeeder::class,
            WorkScheduleSeeder::class,

            // Data Master (Induk)
            TeacherSeeder::class,
            SubjectSeeder::class,
            MajorSeeder::class,
            AcademicYearSeeder::class,

            // Data Relasi
            ClassroomSeeder::class,

            // Data Operasional
            LeaveRequestSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
