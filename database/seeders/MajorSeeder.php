<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Major;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data 5 jurusan
        $majors = [
            [
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'head_of_program_id' => null,
            ],
            [
                'code' => 'TKJ',
                'name' => 'Teknik Komputer dan Jaringan',
                'head_of_program_id' => null,
            ],
            [
                'code' => 'AKKUL',
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'head_of_program_id' => null,
            ],
            [
                'code' => 'PS',
                'name' => 'Perbankan Syariah',
                'head_of_program_id' => null,
            ],
            [
                'code' => 'MPLB',
                'name' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'head_of_program_id' => null,
            ],
        ];

        foreach ($majors as $major) {
            // Menggunakan updateOrCreate agar tidak error jika dijalankan berulang kali
            Major::updateOrCreate(
                ['code' => $major['code']], // Unik berdasarkan kode jurusan
                $major
            );
        }

        $this->command->info('Jurusan RPL, TKJ, AKKUL, PS, dan MPLB berhasil ditambahkan!');
    }
}
