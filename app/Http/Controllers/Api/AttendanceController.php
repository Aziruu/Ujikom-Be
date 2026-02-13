<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    // Koordinat sekolah (titik pusat GPS)
    private $schoolLat = -6.827185;
    private $schoolLng = 107.138055;

    // Radius maksimum absensi manual (meter)
    private $maxRadius = 150;

    /**
     * =========================
     * INDEX
     * =========================
     * Menampilkan log absensi
     * Bisa difilter berdasarkan:
     * - nama / NIP guru
     * - tanggal
     */
    public function index(Request $request)
    {
        $query = Attendance::with('teacher')->latest();

        // Filter pencarian guru
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan tanggal
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Pagination dinamis
        $perPage = $request->query('per_page', 10);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)
        ]);
    }

    /**
     * =========================
     * STORE
     * =========================
     * Menangani:
     * - Absen masuk
     * - Absen pulang
     * - Validasi waktu, lokasi, cuti
     */
    public function store(Request $request)
    {
        // Waktu sekarang (timezone Jakarta)
        $now = Carbon::now('Asia/Jakarta');
        $dateString = $now->format('Y-m-d');

        // Aturan jam absensi
        $jamBatasHadir = Carbon::createFromTime(7, 0, 0, 'Asia/Jakarta');
        $jamBatasTelat = Carbon::createFromTime(8, 0, 0, 'Asia/Jakarta');
        $jamBolehPulang = Carbon::createFromTime(15, 0, 0, 'Asia/Jakarta');

        /**
         * Validasi input
         * Method menentukan field wajib:
         * - RFID → rfid_uid
         * - Manual → teacher_id + GPS
         */
        $request->validate([
            'method'     => 'required|in:rfid,face,qrcode,manual',
            'rfid_uid'   => 'required_if:method,rfid',
            'teacher_id' => 'required_if:method,manual,face|exists:teachers,id',
            'latitude'   => 'required_if:method,manual|numeric',
            'longitude'  => 'required_if:method,manual|numeric',
            'photo'      => 'nullable|string',
        ]);

        /**
         * Validasi lokasi GPS
         * Hanya berlaku untuk absensi manual
         */
        if ($request->method === 'manual') {
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $this->schoolLat,
                $this->schoolLng
            );

            if ($distance > $this->maxRadius) {
                return response()->json([
                    'success' => false,
                    'message' => "Anda berada {$distance}m dari sekolah. Maksimal {$this->maxRadius}m."
                ], 422);
            }
        }

        /**
         * Identifikasi guru
         * - Manual → berdasarkan ID
         * - RFID / QR / Face → berdasarkan UID
         */
        $teacher = $request->method === 'manual' || $request->method === 'face'
            ? Teacher::find($request->teacher_id)
            : Teacher::where('rfid_uid', $request->rfid_uid)->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Guru tidak ditemukan atau kartu belum terdaftar.'
            ], 404);
        }

        /**
         * Cek apakah guru sedang cuti disetujui
         * Jika iya → tidak perlu absen
         */
        $isLeave = LeaveRequest::where('teacher_id', $teacher->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();

        if ($isLeave) {
            return response()->json([
                'success' => false,
                'message' => "Anda sedang cuti yang disetujui."
            ]);
        }

        /**
         * Ambil data absensi hari ini
         * Digunakan untuk menentukan:
         * - Absen masuk
         * - Absen pulang
         */
        $attendance = Attendance::where('teacher_id', $teacher->id)
            ->where('date', $dateString)
            ->first();

        // =====================
        // ABSEN PULANG
        // =====================
        if ($attendance) {

            // Sudah absen masuk & pulang
            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Absensi hari ini sudah lengkap.'
                ]);
            }

            // Belum waktunya pulang
            if ($now->lt($jamBolehPulang)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum waktunya pulang (15:00).'
                ]);
            }

            // Simpan jam pulang
            $attendance->update([
                'check_out' => $now->toTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Absensi pulang berhasil.',
                'data' => $attendance
            ]);
        }

        // =====================
        // ABSEN MASUK
        // =====================

        // Lewat jam 08:00 → dianggap alpa
        if ($now->gt($jamBatasTelat)) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi masuk ditutup. Anda dianggap alpa.'
            ], 422);
        }

        // Penentuan status kehadiran
        $status = 'hadir';
        $lateDuration = 0;

        if ($now->gt($jamBatasHadir)) {
            $status = 'telat';
            $lateDuration = $now->diffInMinutes($jamBatasHadir);
        }

        /**
         * Simpan foto absensi (jika ada)
         * Format base64 → storage public
         */
        $photoPath = null;
        if (!empty($request->photo)) {
            try {
                $image = explode(',', $request->photo)[1];
                $imageName = 'attendance_' . $teacher->id . '_' . time() . '.jpg';

                Storage::disk('public')
                    ->put('attendance_photos/' . $imageName, base64_decode($image));

                $photoPath = 'attendance_photos/' . $imageName;
            } catch (\Exception $e) {
                // Jika gagal simpan foto, absensi tetap lanjut
            }
        }

        // Simpan absensi masuk
        $newAttendance = Attendance::create([
            'teacher_id'    => $teacher->id,
            'date'          => $dateString,
            'check_in'      => $now->toTimeString(),
            'method'        => $request->method,
            'status'        => $status,
            'late_duration' => $lateDuration,
            'photo_path'    => $photoPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => $status === 'telat'
                ? "Terlambat {$lateDuration} menit."
                : 'Absensi tepat waktu.',
            'data' => $newAttendance
        ], 201);
    }

    /**
     * Menghitung jarak GPS (Haversine Formula)
     * Output dalam meter
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($dLon / 2) ** 2;

        return round($earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a))));
    }
}
