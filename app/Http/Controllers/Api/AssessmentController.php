<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * @brief Mengambil daftar guru yang aktif untuk keperluan penilaian.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $teachers = Teacher::select('id', 'name', 'nip', 'photo_url')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $teachers
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @brief Menyimpan transaksi penilaian guru (Header & Detail).
     * @details Menggunakan Database Transaction untuk menjamin integritas data 
     * saat menyimpan header penilaian dan detail skor secara bersamaan.
     * @param \Illuminate\Http\Request $request Payload berisi evaluator_id, evaluatee_id, dan array details.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'evaluator_id' => 'required|exists:users,id',
            'evaluatee_id' => 'required|exists:teachers,id',
            'assessment_date' => 'required|date',
            'period' => 'required|string',
            'general_notes' => 'nullable|string',
            'details' => 'required|array',
            'details.*.category_id' => 'required|exists:assessment_categories,id',
            'details.*.score' => 'required|integer|min:1|max:5',
        ]);

        // Menggunakan DB Transaction untuk integritas data
        DB::beginTransaction();
        try {
            // Simpan Header
            $assessment = Assessment::create([
                'evaluator_id' => $validated['evaluator_id'],
                'evaluatee_id' => $validated['evaluatee_id'],
                'assessment_date' => $validated['assessment_date'],
                'period' => $validated['period'],
                'general_notes' => $validated['general_notes'],
            ]);

            // Simpan Detail Skor
            $detailsData = [];
            foreach ($validated['details'] as $detail) {
                $detailsData[] = [
                    'assessment_id' => $assessment->id,
                    'category_id' => $detail['category_id'],
                    'score' => $detail['score'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $assessment->details()->insert($detailsData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Penilaian berhasil disimpan.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan penilaian.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mengambil data untuk divisualisasikan menjadi Radar Chart
    /**
     * @brief Mengambil data rata-rata skor per kategori untuk visualisasi Radar Chart.
     * @param int $teacherId ID Guru yang dinilai.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRadarChartData($teacherId)
    {
        // Mengambil rata-rata skor per kategori untuk guru tertentu
        $chartData = DB::table('assessment_details')
            ->join('assessments', 'assessment_details.assessment_id', '=', 'assessments.id')
            ->join('assessment_categories', 'assessment_details.category_id', '=', 'assessment_categories.id')
            ->where('assessments.evaluatee_id', $teacherId)
            ->select(
                'assessment_categories.name as category',
                DB::raw('ROUND(AVG(assessment_details.score), 1) as average_score')
            )
            ->groupBy('assessment_categories.id', 'assessment_categories.name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $chartData
        ]);
    }

    // Mengambil riwayat catatan evaluasi untuk satu guru
    /**
     * @brief Mengambil riwayat catatan evaluasi lengkap untuk satu guru.
     * @param int $teacherId ID Guru.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeacherHistory($teacherId)
    {
        $history = Assessment::with('evaluator:id,name') // Mengambil nama penilai
            ->where('evaluatee_id', $teacherId)
            ->orderBy('assessment_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
