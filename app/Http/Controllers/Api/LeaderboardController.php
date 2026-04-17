<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * @brief Mengambil data analitik integritas (Poin Tertinggi & Terendah) dengan Pagination.
     */
    public function index(Request $request)
    {
        // Limit 10 data per scroll sesuai request
        $limit = $request->query('limit', 10);

        // Top Guru Paling Disiplin
        $topTeachers = Teacher::select('id', 'name', 'nip', 'photo_url', 'point_balance')
            ->where('is_active', true)
            ->orderBy('point_balance', 'desc')
            ->paginate($limit);

        // Bottom Guru Paling Sering Telat
        $bottomTeachers = Teacher::select('id', 'name', 'nip', 'photo_url', 'point_balance')
            ->where('is_active', true)
            ->orderBy('point_balance', 'asc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data Leaderboard berhasil ditarik.',
            'data' => [
                'top_disciplined' => $topTeachers->items(),
                'needs_improvement' => $bottomTeachers->items(),
                'has_more_top' => $topTeachers->hasMorePages(),
                'has_more_bottom' => $bottomTeachers->hasMorePages(),
            ]
        ]);
    }
}