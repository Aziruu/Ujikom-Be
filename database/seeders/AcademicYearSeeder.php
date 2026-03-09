<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;

class AcademicYearSeeder extends Seeder
{
    /**
     * Mengisi data tahun ajaran secara otomatis dari periode 2010 hingga 2025.
     * Setiap tahun memiliki dua entri semester (Ganjil dan Genap).
     */
    public function run(): void
    {
        for ($i = 2010; $i <= 2025; $i++) {
            $nextYear = $i + 1;
            $yearString = "{$i}/{$nextYear}";

            $semesters = ['ganjil', 'genap'];

            foreach ($semesters as $semester) {
                AcademicYear::updateOrCreate(
                    [
                        'name' => "{$yearString} " . ucfirst($semester),
                        'years' => $yearString,
                        'semester' => $semester,
                    ],
                    [
                        // Set aktif hanya untuk semester terbaru (contoh: 2025/2026 Genap)
                        'is_active' => ($i === 2025 && $semester === 'genap') ? true : false,
                    ]
                );
            }
        }

        $this->command->info('Proses pengisian data Tahun Ajaran periode 2010-2025 berhasil diselesaikan.');
    }
}
