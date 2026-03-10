<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeachingSchedule;
use App\Models\Subject;

/**
 * Class TeachingScheduleSeeder
 * * Mengatur data awal jadwal mengajar (Teaching Schedule) untuk periode 
 * Semester Genap Tahun Pelajaran 2025/2026.
 * Data disusun berdasarkan dokumen jadwal resmi satuan pendidikan.
 */
class TeachingScheduleSeeder extends Seeder
{
    /**
     * Menjalankan proses seeding data jadwal mengajar.
     * Khusus untuk entitas Kelas XII RPL 1 (ID: 41).
     *
     * @return void
     */
    public function run(): void
    {
        // Referensi ID Kelas target (XII RPL 1)
        $classId = 41;

        /**
         * Pemetaan referensi Mata Pelajaran (Subjects)
         * Mengambil ID berdasarkan pencocokan nama pada tabel subjects.
         */
        $subjects = [
            'rpl'       => Subject::where('name', 'like', '%Produktif%')->orWhere('name', 'like', '%RPL%')->first()?->id,
            'pancasila' => Subject::where('name', 'like', '%Pancasila%')->first()?->id,
            'kik'       => Subject::where('name', 'like', '%Produktif%')->first()?->id,
            'inggris'   => Subject::where('name', 'like', '%Inggris%')->first()?->id,
            'indo'      => Subject::where('name', 'like', '%Indonesia%')->first()?->id,
            'jepang'    => Subject::where('name', 'like', '%Lokal%')->first()?->id,
            'mtk'       => Subject::where('name', 'like', '%Matematika%')->first()?->id,
            'agama'     => Subject::where('name', 'like', '%Agama%')->first()?->id,
            'bk'        => Subject::where('name', 'like', '%Konseling%')->first()?->id,
        ];

        /**
         * Kumpulan data jadwal berdasarkan periode hari dan alokasi waktu (Slot)
         * Mengacu pada dokumen PDF Jadwal Pelajaran SMKN 1 Cianjur.
         */
        $schedules = [
            // Jadwal Hari Senin
            ['teacher_id' => 77, 'subject_id' => $subjects['rpl'], 'day' => 'senin', 'start' => '07:10', 'end' => '08:30'],
            ['teacher_id' => 6,  'subject_id' => $subjects['bk'],  'day' => 'senin', 'start' => '08:30', 'end' => '09:10'],
            ['teacher_id' => 77, 'subject_id' => $subjects['rpl'], 'day' => 'senin', 'start' => '09:25', 'end' => '11:25'],

            // Jadwal Hari Selasa
            ['teacher_id' => 76, 'subject_id' => $subjects['rpl'], 'day' => 'selasa', 'start' => '07:10', 'end' => '09:10'],
            ['teacher_id' => 116, 'subject_id' => $subjects['pancasila'], 'day' => 'selasa', 'start' => '09:25', 'end' => '11:25'],
            ['teacher_id' => 2,  'subject_id' => $subjects['rpl'], 'day' => 'selasa', 'start' => '12:30', 'end' => '15:50'],

            // Jadwal Hari Rabu
            ['teacher_id' => 76, 'subject_id' => $subjects['rpl'], 'day' => 'rabu', 'start' => '07:10', 'end' => '10:05'],
            ['teacher_id' => 23, 'subject_id' => $subjects['kik'], 'day' => 'rabu', 'start' => '10:05', 'end' => '12:30'],
            ['teacher_id' => 88, 'subject_id' => $subjects['inggris'], 'day' => 'rabu', 'start' => '13:10', 'end' => '14:30'],
            ['teacher_id' => 6,  'subject_id' => $subjects['bk'],  'day' => 'rabu', 'start' => '14:30', 'end' => '15:50'],

            // Jadwal Hari Kamis
            ['teacher_id' => 38, 'subject_id' => $subjects['rpl'], 'day' => 'kamis', 'start' => '07:10', 'end' => '09:10'],
            ['teacher_id' => 111, 'subject_id' => $subjects['jepang'], 'day' => 'kamis', 'start' => '09:25', 'end' => '11:25'],
            ['teacher_id' => 65, 'subject_id' => $subjects['indo'], 'day' => 'kamis', 'start' => '12:30', 'end' => '14:30'],
            ['teacher_id' => 88, 'subject_id' => $subjects['inggris'], 'day' => 'kamis', 'start' => '14:30', 'end' => '15:50'],

            // Jadwal Hari Jumat
            ['teacher_id' => 38, 'subject_id' => $subjects['mtk'], 'day' => 'jumat', 'start' => '07:10', 'end' => '08:30'],
            ['teacher_id' => 18, 'subject_id' => $subjects['agama'], 'day' => 'jumat', 'start' => '08:30', 'end' => '09:50'],
        ];

        foreach ($schedules as $sched) {
            TeachingSchedule::create([
                'teacher_id'   => $sched['teacher_id'],
                'classroom_id' => $classId,
                'subject_id'   => $sched['subject_id'],
                'day'          => $sched['day'],
                'start_time'   => $sched['start'],
                'end_time'     => $sched['end'],
            ]);
        }

        $this->command->info('Sinkronisasi data jadwal mengajar Kelas XII RPL 1 berhasil dilakukan.');
    }
}
