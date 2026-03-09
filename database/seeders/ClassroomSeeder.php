<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Http;

class ClassroomSeeder extends Seeder
{
    /**
     * Mengambil data kelas dari API eksternal dan melakukan sinkronisasi dengan 
     * data Jurusan serta Tahun Ajaran yang aktif di sistem.
     */
    public function run(): void
    {
        // 1. Ambil Tahun Ajaran Aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            $this->command->error('Gagal: Tidak ada Tahun Ajaran yang aktif. Jalankan AcademicYearSeeder terlebih dahulu.');
            return;
        }

        // 2. Ambil referensi semua Jurusan untuk mapping ID
        $majors = Major::all();
        if ($majors->isEmpty()) {
            $this->command->error('Gagal: Data Jurusan kosong. Jalankan MajorSeeder terlebih dahulu.');
            return;
        }

        // 3. Request data dari API eksternal
        $response = Http::get('https://zieapi.zielabs.id/api/getkelas?tahun=2025');

        if ($response->successful()) {
            $apiData = $response->json();
            $classes = $apiData['data'] ?? [];

            foreach ($classes as $classItem) {
                if (!isset($classItem['nama_kelas'])) {
                    $this->command->warn('Ada data kelas yang dilewati karena format tidak sesuai.');
                    continue;
                }

                $className = $classItem['nama_kelas'];

                // Logika penentuan grade_level berdasarkan awalan nama kelas
                $gradeLevel = 10;
                if (str_contains($className, 'XI ')) $gradeLevel = 11;
                if (str_contains($className, 'XII ')) $gradeLevel = 12;

                // Logika pencocokan major_id berdasarkan kode di nama kelas (RPL, TKJ, dll)
                $majorId = null;
                foreach ($majors as $major) {
                    if (stripos($className, $major->code) !== false) {
                        $majorId = $major->id;
                        break;
                    }
                }

                if ($majorId) {
                    Classroom::updateOrCreate(
                        [
                            'name' => $className,
                            'academic_year_id' => $activeYear->id,
                        ],
                        [
                            'grade_level' => $gradeLevel,
                            'major_id' => $majorId,
                            'homeroom_teacher_id' => null,
                        ]
                    );
                }
            }

            $this->command->info('Sinkronisasi data Kelas dari API berhasil dilaksanakan.');
        } else {
            $this->command->error('Gagal mengambil data dari API eksternal.');
        }
    }
}
