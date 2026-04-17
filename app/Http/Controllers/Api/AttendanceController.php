<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use App\Models\PointLedgers;
use App\Models\PointRules;
use App\Models\SchoolLocation;
use App\Models\UserTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    /**
     * @brief Menampilkan daftar riwayat absensi.
     * @details Mendukung filter pencarian berdasarkan nama/NIP guru, filter ID guru tertentu, 
     * filter periode (hari ini, bulan ini, tahun ini), serta fitur export data tanpa pagination.
     * @param \Illuminate\Http\Request $request Data filter (search, teacher_id, period, date, export).
     * @return \Illuminate\Http\JsonResponse Daftar data absensi dalam format JSON.
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

        // Filter spesifik untuk guru yang sedang login
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter Periode Cepat (Hari ini, Bulan ini, Tahun ini)
        if ($request->filled('period')) {
            $now = Carbon::now('Asia/Jakarta');
            if ($request->period === 'today') {
                $query->whereDate('date', $now->toDateString());
            } elseif ($request->period === 'month') {
                $query->whereMonth('date', $now->month)->whereYear('date', $now->year);
            } elseif ($request->period === 'year') {
                $query->whereYear('date', $now->year);
            }
        }
        // Filter Tanggal Spesifik
        elseif ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // JIKA MODE EXPORT: Kembalikan SEMUA data tanpa pagination (limit)
        if ($request->boolean('export')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()
            ]);
        }

        $perPage = $request->query('per_page', 10);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)
        ]);
    }

    /**
     * @brief Memproses data transaksi absensi masuk maupun pulang.
     * * @details Fungsi ini menangani validasi multi-lokasi (berdasarkan jarak radius GPS), 
     * pengecekan status cuti guru, serta penentuan status keterlambatan berdasarkan 
     * tenggat waktu yang    ditetapkan.
     *
     * @param \Illuminate\Http\Request $request Data payload dari client (method, rfid_uid, latitude, longitude).
     * @return \Illuminate\Http\JsonResponse Mengembalikan format JSON berisi status keberhasilan dan data absensi.
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

        // Validasi Lokasi Kampus
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
        $status = 'hadir';
        $lateDuration = 0;
        $usedTokenId = null;

        if ($now->gt($jamBatasTelat)) {
            // Maksa absen lewat jam 08:00 -> Langsung Alpa!
            $status = 'alpa';
            $lateDuration = $now->diffInMinutes($jamBatasHadir);
            // Tidak usah cek token karena sudah fatal (Alpa)
        } elseif ($now->gt($jamBatasHadir)) {
            // Telat biasa (antara 07:00 - 08:00)
            $status = 'telat';
            $lateDuration = $now->diffInMinutes($jamBatasHadir);

            // 1. FASE INTERCEPTOR: Cek apakah user punya token kompensasi
            $availableToken = UserTokens::where('teacher_id', $teacher->id)
                ->where('status', 'AVAILABLE')
                ->whereHas('item', function ($query) use ($lateDuration) {
                    $query->where('item_name', 'like', '%Telat%');
                })->first();

            if ($availableToken) {
                $status = 'hadir_token';
                $usedTokenId = $availableToken->id;
            }
        }

        // ... [Kode Upload Foto Tetap Sama] ...
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

        // Mulai Transaksi Database yang Aman
        DB::beginTransaction();
        try {
            // 2. Simpan Data Absensi
            $newAttendance = Attendance::create([
                'teacher_id'    => $teacher->id,
                'date'          => $dateString,
                'check_in'      => $now->toTimeString(),
                'method'        => $request->method,
                'status'        => $status,
                'late_duration' => $lateDuration,
                'photo_path'    => $photoPath,
            ]);

            // 3. Eksekusi Token (Jika Interceptor Berjalan)
            if ($usedTokenId) {
                UserTokens::where('id', $usedTokenId)->update([
                    'status' => 'USED',
                    'used_at_attendance_id' => $newAttendance->id,
                    'used_at' => $now
                ]);
            }

            // 4. FASE RULE ENGINE: Kalkulasi Poin Integritas Dinamis
            $totalPointModifier = 0;
            $appliedRules = [];

            if ($status === 'alpa') {
                // LOGIKA KHUSUS ALPA: Langsung cari rule alpa atau tembak -10
                $alpaRule = PointRules::where('rule_name', 'like', '%Alpa%')->first();
                $totalPointModifier = $alpaRule ? $alpaRule->point_modifier : -10;
                $appliedRules[] = $alpaRule ? $alpaRule->rule_name : 'Alpa (Sistem)';
                
            } elseif ($status !== 'hadir_token') {
                // LOGIKA HADIR/TELAT BIASA (Evaluasi Dinamis)
                $minutesDiff = (int) $jamBatasHadir->diffInMinutes($now, false);
                $rules = PointRules::where('is_active', true)
                            ->where('rule_name', 'not like', '%Alpa%') // Skip rule alpa disini
                            ->get();

                foreach ($rules as $rule) {
                    $conditionMet = false;
                    $ruleVal = $rule->condition_value;

                    switch ($rule->condition_operator) {
                        case '<':  $conditionMet = $minutesDiff < (int)$ruleVal; break;
                        case '<=': $conditionMet = $minutesDiff <= (int)$ruleVal; break;
                        case '>':  $conditionMet = $minutesDiff > (int)$ruleVal; break;
                        case '>=': $conditionMet = $minutesDiff >= (int)$ruleVal; break;
                        case '=':  $conditionMet = $minutesDiff == (int)$ruleVal; break;
                        case 'BETWEEN':
                            $vals = explode(',', $ruleVal);
                            if (count($vals) == 2) {
                                $conditionMet = ($minutesDiff >= (int)trim($vals[0]) && $minutesDiff <= (int)trim($vals[1]));
                            }
                            break;
                    }

                    if ($conditionMet) {
                        $totalPointModifier += $rule->point_modifier;
                        $appliedRules[] = $rule->rule_name;
                    }
                }
            }

            // 5. Pencatatan Ledger (Mutasi Poin)
            if ($totalPointModifier !== 0) {
                $lockedTeacher = Teacher::where('id', $teacher->id)->lockForUpdate()->first();
                $newBalance = $lockedTeacher->point_balance + $totalPointModifier;

                PointLedgers::create([
                    'teacher_id'       => $teacher->id,
                    'transaction_type' => $totalPointModifier > 0 ? 'EARN' : 'PENALTY',
                    'amount'           => $totalPointModifier,
                    'current_balance'  => $newBalance,
                    'description'      => "Sistem Absensi: " . implode(', ', $appliedRules)
                ]);

                // Pakai save() biar nggak kena mass assignment trap
                $lockedTeacher->point_balance = $newBalance;
                $lockedTeacher->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $status === 'alpa' 
                             ? 'Anda absen melewati batas akhir. Status Alpa & poin dikurangi!' 
                             : ($status === 'hadir_token' ? 'Absensi terselamatkan oleh Token!' : 'Absensi tercatat.'),
                'data' => $newAttendance
            ], 201);
            
        } catch (\Throwable $e) { // Pakai Throwable biar aman dari deadlock
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses absensi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @brief Menghitung jarak antara dua titik koordinat (Haversine Formula).
     * @details Digunakan untuk memvalidasi apakah pengguna berada di dalam radius lokasi sekolah yang diizinkan.
     * @param float $lat1 Latitude asal.
     * @param float $lon1 Longitude asal.
     * @param float $lat2 Latitude tujuan.
     * @param float $lon2 Longitude tujuan.
     * @return float Jarak dalam satuan meter.
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
