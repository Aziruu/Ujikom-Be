<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Teacher;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi input (Kita butuh UID kartunya dan metodenya)
        $request->validate([
            'rfid_uid' => 'required|string',
            'method'   => 'required|in:rfid,face,qrcode,manual', // Sesuai ENUM database
        ]);

        // 2. Cari Guru berdasarkan RFID UID
        $teacher = Teacher::where('rfid_uid', $request->rfid_uid)->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu tidak dikenali! Silakan daftar dulu.',
            ], 404);
        }

        // 3. Cek apakah hari ini dia sudah absen masuk?
        $today = Carbon::today();
        $attendance = Attendance::where('teacher_id', $teacher->id)
                                ->where('date', $today)
                                ->first();

        if ($attendance) {
            // Kalau sudah ada datanya, kita kabari saja (Nanti bisa dikembangin jadi Check-out)
            return response()->json([
                'success' => false, // False biar frontend tau ini bukan absen baru
                'message' => "Halo {$teacher->name}, kamu sudah absen masuk jam {$attendance->check_in} tadi.",
                'data'    => $attendance
            ], 200);
        }

        // 4. Hitung status (Telat atau Hadir)
        // Misal jam masuk jam 07:00. Lewat dari itu dianggap telat.
        $jamMasuk = Carbon::now();
        $batasMasuk = Carbon::createFromTime(7, 0, 0); // Jam 07:00 pagi
        
        $status = 'hadir';
        $lateDuration = 0;

        if ($jamMasuk->gt($batasMasuk)) {
            $status = 'telat';
            $lateDuration = $jamMasuk->diffInMinutes($batasMasuk); // Hitung telat berapa menit
        }

        // 5. Simpan Absensi Baru
        $newAttendance = Attendance::create([
            'teacher_id'    => $teacher->id,
            'date'          => $today,
            'check_in'      => $jamMasuk->toTimeString(),
            'method'        => $request->method, // 'rfid'
            'status'        => $status,
            'late_duration' => $lateDuration
        ]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil! Selamat bekerja, {$teacher->name}.",
            'data'    => $newAttendance,
            'teacher' => $teacher
        ], 201);
    }
}