<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Teacher;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua guru yang ada
        $teachers = Teacher::all();

        if ($teachers->isEmpty()) {
            $this->command->info('data gurunya kosong! Isi table teachers dulu ya');
            return;
        }

        // Kita buat data untuk 1 tahun ke belakang sampai hari ini
        $startDate = Carbon::now()->subYear(); 
        $endDate = Carbon::now();
        $period = CarbonPeriod::create($startDate, $endDate);

        $data = [];

        foreach ($period as $date) {
            // Kita skip hari Sabtu dan Minggu ya, kan sekolah libur~ 
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($teachers as $teacher) {
                // Simulasi: 10% kemungkinan guru tidak masuk (izin/sakit/alpa)
                $absentChance = rand(1, 100);
                
                if ($absentChance <= 10) {
                    $statusOptions = ['izin', 'sakit', 'alpa'];
                    $data[] = [
                        'teacher_id'    => $teacher->id,
                        'date'          => $date->format('Y-m-d'),
                        'check_in'      => null,
                        'check_out'     => null,
                        'method'        => null,
                        'status'        => $statusOptions[array_rand($statusOptions)],
                        'late_duration' => 0,
                        'created_at'    => $date,
                        'updated_at'    => $date,
                    ];
                    continue;
                }

                // Tentukan jam masuk (random antara 06:30 sampai 08:15)
                $checkInTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
                $checkInTime->addHours(6)->addMinutes(rand(30, 135)); // 06:30 + (0-105 menit)

                $limitHadir = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 07:00:00');
                $limitAlpa  = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 08:00:00');

                $status = 'hadir';
                $lateDuration = 0;

                // Logika status berdasarkan jam masuk (sesuai controller kamu)
                if ($checkInTime->gt($limitAlpa)) {
                    $status = 'alpa';
                } elseif ($checkInTime->gt($limitHadir)) {
                    $status = 'telat';
                    $lateDuration = $checkInTime->diffInMinutes($limitHadir);
                }

                // Jam pulang (random antara jam 15:00 sampai 17:00)
                $checkOutTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 15:00:00')
                                ->addMinutes(rand(0, 120));

                $data[] = [
                    'teacher_id'    => $teacher->id,
                    'date'          => $date->format('Y-m-d'),
                    'check_in'      => $checkInTime->format('H:i:s'),
                    'check_out'     => ($status !== 'alpa') ? $checkOutTime->format('H:i:s') : null,
                    'method'        => rand(0, 1) ? 'rfid' : 'face', // Random metode rfid atau wajah
                    'status'        => $status,
                    'late_duration' => $lateDuration,
                    'created_at'    => $date,
                    'updated_at'    => $date,
                ];

                // Biar nggak memory limit kalau datanya ribuan, kita insert per 100 data
                if (count($data) >= 100) {
                    Attendance::insert($data);
                    $data = [];
                }
            }
        }

        // Insert sisa datanya
        if (!empty($data)) {
            Attendance::insert($data);
        }

        $this->command->info('Beres! Data absen setahun sudah masuk ke database. Capek juga ya... 💋');
    }
}