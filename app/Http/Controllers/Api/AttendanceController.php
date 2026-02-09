<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Teacher;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Query Dasar + Relasi ke Guru
        $query = Attendance::with('teacher')->latest();

        // 2. Filter Pencarian (Nama atau NIP)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // 3. Filter Tanggal (Opsional, biar bisa cek hari tertentu)
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('date', $request->date);
        }

        // 4. Ambil data (Pagination biar gak berat kalau data ribuan)
        // Kita ambil 50 data per halaman
        $logs = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data'    => $logs
        ]);
    }

    public function store(Request $request)
    {
        // --- 1. VALIDASI DIPERBAIKI ---
        $request->validate([
            'method'      => 'required|in:rfid,face,qrcode,manual',
            // RFID wajib CUMA kalau method = rfid
            'rfid_uid'    => 'required_if:method,rfid',
            // Teacher ID wajib CUMA kalau method = manual
            'teacher_id'  => 'required_if:method,manual|exists:teachers,id',
        ]);

        $teacher = null;

        // --- 2. LOGIC PENCARIAN GURU ---
        if ($request->method === 'manual') {
            $teacher = Teacher::find($request->teacher_id);
        } else {
            // Jika RFID, cari berdasarkan UID
            $teacher = Teacher::where('rfid_uid', $request->rfid_uid)->first();
        }

        // Jika guru tidak ditemukan (RFID tidak terdaftar atau ID salah)
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Data guru tidak ditemukan dalam sistem.',
            ], 404);
        }

        // --- 3. CEK APAKAH SUDAH ABSEN HARI INI ---
        $today = Carbon::today();
        $attendance = Attendance::where('teacher_id', $teacher->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            return response()->json([
                'success' => false,
                'message' => "Halo {$teacher->name}, Anda sudah melakukan absensi masuk jam {$attendance->check_in}.",
                'data'    => $attendance
            ], 200);
        }

        // --- 4. HITUNG KETERLAMBATAN ---
        $jamMasuk = Carbon::now();
        // Atur jam masuk (misal jam 07:00 pagi)
        $batasMasuk = Carbon::createFromTime(7, 0, 0);

        $status = 'hadir';
        $lateDuration = 0;

        if ($jamMasuk->gt($batasMasuk)) {
            $status = 'telat';
            $lateDuration = $jamMasuk->diffInMinutes($batasMasuk);
        }

        // --- 5. SIMPAN DATA ---
        $newAttendance = Attendance::create([
            'teacher_id'    => $teacher->id,
            'date'          => $today,
            'check_in'      => $jamMasuk->toTimeString(),
            'method'        => $request->method,
            'status'        => $status,
            'late_duration' => $lateDuration
        ]);

        return response()->json([
            'success' => true,
            'message' => "Absensi Berhasil! Selamat mengajar, {$teacher->name}.",
            'data'    => $newAttendance,
            'teacher' => $teacher
        ], 201);
    }
}
