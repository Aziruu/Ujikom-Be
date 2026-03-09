<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use App\Models\SchoolLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    /**
     * =========================
     * INDEX
     * =========================
     */
    public function index(Request $request)
    {
        $query = Attendance::with('teacher')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

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
     */
    public function store(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');
        $dateString = $now->format('Y-m-d');

        $jamBatasHadir = Carbon::createFromTime(7, 0, 0, 'Asia/Jakarta');
        $jamBatasTelat = Carbon::createFromTime(8, 0, 0, 'Asia/Jakarta');
        $jamBolehPulang = Carbon::createFromTime(15, 0, 0, 'Asia/Jakarta');

        $request->validate([
            'method'     => 'required|in:rfid,face,manual',
            'rfid_uid'   => 'required_if:method,rfid',
            'teacher_id' => 'required_if:method,manual,face|exists:teachers,id',
            'latitude'   => 'required_if:method,manual, face|numeric',
            'longitude'  => 'required_if:method,manual, face|numeric',
            'photo'      => 'nullable|string',
        ]);

        /**
         * =========================================
         * VALIDASI MULTI-LOKASI KAMPUS
         * =========================================
         */
        if ($request->method === 'manual' || $request->method === 'face') {
            // Ambil semua data kampus dari database
            $locations = SchoolLocation::all();

            if ($locations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sistem belum memiliki titik lokasi kampus. Hubungi Admin!'
                ], 422);
            }

            $isValidLocation = false;
            $closestDistance = PHP_INT_MAX;

            // Looping semua kampus, cek apakah guru ada di salah satu radius kampus
            foreach ($locations as $location) {
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $location->latitude,
                    $location->longitude
                );

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                }

                // Kalau masuk di radius kampus ini, langsung lolos!
                if ($distance <= $location->radius) {
                    $isValidLocation = true;
                    break;
                }
            }

            // Kalau setelah dicek di semua kampus tetep di luar radius
            if (!$isValidLocation) {
                return response()->json([
                    'success' => false,
                    'message' => "Anda berada di luar jangkauan radius kampus mana pun. Jarak terdekat Anda: {$closestDistance}m."
                ], 422);
            }
        }

        $teacher = $request->method === 'manual' || $request->method === 'face'
            ? Teacher::find($request->teacher_id)
            : Teacher::where('rfid_uid', $request->rfid_uid)->first();

        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan atau kartu belum terdaftar.'], 404);
        }

        $isLeave = LeaveRequest::where('teacher_id', $teacher->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();

        if ($isLeave) {
            return response()->json(['success' => false, 'message' => "Anda sedang cuti yang disetujui."]);
        }

        $attendance = Attendance::where('teacher_id', $teacher->id)->where('date', $dateString)->first();

        // ABSEN PULANG
        if ($attendance) {
            if ($attendance->check_out) {
                return response()->json(['success' => false, 'message' => 'Absensi hari ini sudah lengkap.']);
            }
            if ($now->lt($jamBolehPulang)) {
                return response()->json(['success' => false, 'message' => 'Belum waktunya pulang (15:00).']);
            }
            $attendance->update(['check_out' => $now->toTimeString()]);
            return response()->json(['success' => true, 'message' => 'Absensi pulang berhasil.', 'data' => $attendance]);
        }

        // ABSEN MASUK
        if ($now->gt($jamBatasTelat)) {
            return response()->json(['success' => false, 'message' => 'Absensi masuk ditutup. Anda dianggap alpa.'], 422);
        }

        $status = 'hadir';
        $lateDuration = 0;
        if ($now->gt($jamBatasHadir)) {
            $status = 'telat';
            $lateDuration = $now->diffInMinutes($jamBatasHadir);
        }

        $photoPath = null;
        if (!empty($request->photo)) {
            try {
                $image = explode(',', $request->photo)[1];
                $imageName = 'attendance_' . $teacher->id . '_' . time() . '.jpg';
                Storage::disk('public')->put('attendance_photos/' . $imageName, base64_decode($image));
                $photoPath = 'attendance_photos/' . $imageName;
            } catch (\Exception $e) {
            }
        }

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
            'message' => $status === 'telat' ? "Terlambat {$lateDuration} menit." : 'Absensi tepat waktu.',
            'data' => $newAttendance
        ], 201);
    }

    /**
     * Menghitung jarak GPS (Haversine Formula) 
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
