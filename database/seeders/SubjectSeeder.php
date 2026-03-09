<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Menjalankan proses pengisian data awal (seeding) untuk tabel mata pelajaran.
     * Data disusun berdasarkan standar kurikulum nasional yang berlaku.
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Pendidikan Agama dan Budi Pekerti'],
            ['name' => 'Pendidikan Pancasila'],
            ['name' => 'Bahasa Indonesia'],
            ['name' => 'Matematika'],
            ['name' => 'Bahasa Inggris'],
            ['name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)'],
            ['name' => 'Sejarah'],
            ['name' => 'Seni Budaya'],
            ['name' => 'Projek Ilmu Pengetahuan Alam dan Sosial (IPAS)'],
            ['name' => 'Informatika'],
            ['name' => 'Produktif (Kejuruan)'],
            ['name' => 'Muatan Lokal'],
            ['name' => 'Bimbingan Konseling'],
        ];

        foreach ($subjects as $subject) {
            // Menggunakan updateOrCreate untuk memastikan integritas data dan mencegah duplikasi
            Subject::updateOrCreate(
                ['name' => $subject['name']],
                $subject
            );
        }

        $this->command->info('Proses seeding mata pelajaran telah berhasil diselesaikan dengan parameter kurikulum nasional.');
    }
}
