<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\AcademicYear;

class ClassroomSeeder extends Seeder
{
    /**
     */
    public function run(): void
    {
        // Ambil Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            $this->command->error('Gagal: Pastikan AcademicYearSeeder sudah dijalankan dan ada tahun yang aktif.');
            return;
        }

        // Definisi Struktur Jurusan dan Jumlah Kelas
        $grades = [10, 11, 12];
        $majorConfigs = [
            'TKJ'   => 3,
            'RPL'   => 2,
            'AKKUL' => 4,
            'PS'    => 4,
            'MPLB'  => 5,
        ];

        foreach ($grades as $grade) {
            foreach ($majorConfigs as $code => $count) {

                // Penyesuaian khusus: Kelas 10 RPL memiliki 3 Kelas
                $currentCount = $count;
                if ($grade === 10 && $code === 'RPL') {
                    $currentCount = 3;
                }

                // Ambil ID Jurusan berdasarkan kode
                $major = Major::where('code', $code)->first();

                if ($major) {
                    for ($i = 1; $i <= $currentCount; $i++) {
                        $romanGrade = $this->getRoman($grade);
                        $className = "{$romanGrade} {$code}-{$i}";

                        Classroom::updateOrCreate(
                            [
                                'name'             => $className,
                                'academic_year_id' => $activeYear->id,
                            ],
                            [
                                'grade_level'         => $grade,
                                'homeroom_teacher_id' => null, 
                                // Wali kelas dikosongkan untuk input manual nanti
                                'major_id'            => $major->id,
                            ]
                        );
                    }
                }
            }
        }

        $this->command->info('Data kelas (10, 11, 12) berhasil dibuat dengan sinkronisasi jurusan.');
    }

    /**
     * Helper untuk mengubah angka grade menjadi format romawi
     */
    private function getRoman($number)
    {
        $map = [12 => 'XII', 11 => 'XI', 10 => 'X'];
        return $map[$number] ?? $number;
    }
}
