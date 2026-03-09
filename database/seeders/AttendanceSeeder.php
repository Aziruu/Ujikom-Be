<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::all();
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();
        $period = CarbonPeriod::create($startDate, $endDate);

        $data = [];

        foreach ($period as $date) {
            if ($date->isWeekend()) continue;

            $dateString = $date->format('Y-m-d');

            foreach ($teachers as $teacher) {
                // SINKRONISASI: Cek apakah ada Izin yang disetujui (Approved) untuk hari ini
                $approvedLeave = LeaveRequest::where('teacher_id', $teacher->id)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $dateString)
                    ->whereDate('end_date', '>=', $dateString)
                    ->first();

                if ($approvedLeave) {
                    // Jika ada izin disetujui, catat status sesuai tipe izin (sakit/izin)
                    $data[] = $this->formatRow($teacher->id, $date, null, null, $approvedLeave->type, 0);
                } else {
                    // Jika tidak ada izin, jalankan logika absensi normal (Hadir/Telat/Alpa)
                    $this->processNormalAttendance($data, $teacher, $date);
                }

                if (count($data) >= 100) {
                    Attendance::insert($data);
                    $data = [];
                }
            }
        }

        if (!empty($data)) Attendance::insert($data);
        $this->command->info('Data Absensi telah berhasil disinkronkan dengan data Pengajuan Cuti.');
    }

    private function processNormalAttendance(&$data, $teacher, $date)
    {
        $checkInTime = $date->copy()->setTime(6, 0);
        $dist = rand(1, 100);

        // Mayoritas datang pagi (95%) sisanya alpa mendadak/unplanned (5%)
        if ($dist <= 5) {
            $data[] = $this->formatRow($teacher->id, $date, null, null, 'alpa', 0);
            return;
        }

        // Penentuan jam masuk (6:00 - 7:59)
        $checkInTime->addMinutes(rand(0, 119));
        $limitHadir = $date->copy()->setTime(7, 0, 0);

        $status = $checkInTime->gt($limitHadir) ? 'telat' : 'hadir';
        $late = $status === 'telat' ? $checkInTime->diffInMinutes($limitHadir) : 0;
        $out = $date->copy()->setTime(15, 0)->addMinutes(rand(5, 60))->format('H:i:s');

        $data[] = $this->formatRow($teacher->id, $date, $checkInTime->format('H:i:s'), $out, $status, $late, rand(0, 1) ? 'rfid' : 'face');
    }

    private function formatRow($tid, $d, $in, $out, $stat, $late, $met = null)
    {
        return [
            'teacher_id' => $tid,
            'date' => $d->format('Y-m-d'),
            'check_in' => $in,
            'check_out' => $out,
            'method' => $met,
            'status' => $stat,
            'late_duration' => $late,
            'created_at' => $d,
            'updated_at' => $d,
        ];
    }
}
