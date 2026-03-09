<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveRequest;
use App\Models\Teacher;
use Carbon\Carbon;

class LeaveRequestSeeder extends Seeder
{
    /**
     * Mengisi data pengajuan izin dan sakit guru.
     * Menggunakan rasio verifikasi 90% disetujui dan 10% ditolak/pending.
     */
    public function run(): void
    {
        $teachers = Teacher::all();
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();

        foreach ($teachers as $teacher) {
            // Simulasi: Setiap guru rata-rata memiliki 3-5 kali izin dalam setahun
            $totalRequests = rand(3, 5);

            for ($i = 0; $i < $totalRequests; $i++) {
                $type = rand(0, 1) ? 'sakit' : 'izin';
                $duration = rand(1, 3); // Lama izin 1-3 hari
                
                // Pilih tanggal acak dalam satu tahun terakhir
                $randomDate = Carbon::now()->subDays(rand(1, 365));
                $start = $randomDate->copy();
                $end = $randomDate->copy()->addDays($duration - 1);

                // Rasio Verifikasi 90% Approved, 10% Rejected
                $verificationChance = rand(1, 100);
                $status = ($verificationChance <= 90) ? 'approved' : 'rejected';

                LeaveRequest::create([
                    'teacher_id' => $teacher->id,
                    'start_date' => $start->format('Y-m-d'),
                    'end_date'   => $end->format('Y-m-d'),
                    'type'       => $type,
                    'reason'     => "Permohonan {$type} dikarenakan urusan yang mendesak.",
                    'status'     => $status,
                    'admin_note' => $status === 'rejected' ? 'Alasan tidak cukup kuat atau tenaga pengajar sedang minim.' : 'Disetujui oleh Admin.',
                    'created_at' => $start->subDays(1), // Dibuat H-1 sebelum izin
                ]);
            }
        }

        $this->command->info('Data Pengajuan Izin/Sakit telah berhasil disinkronkan dengan rasio verifikasi 90%.');
    }
}