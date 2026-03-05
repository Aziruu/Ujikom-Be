<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeachingSchedule;
use Illuminate\Http\Request;

class TeachingScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TeachingSchedule::with(['teacher', 'classroom', 'subject']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Trik jitu urutin hari di SQLite/MySQL langsung dari Database!
        $query->orderByRaw("
            CASE day
                WHEN 'senin' THEN 1
                WHEN 'selasa' THEN 2
                WHEN 'rabu' THEN 3
                WHEN 'kamis' THEN 4
                WHEN 'jumat' THEN 5
                WHEN 'sabtu' THEN 6
                WHEN 'minggu' THEN 7
                ELSE 8
            END
        ")->orderBy('start_time');

        $schedules = $query->paginate(10);

        return response()->json([
            'success' => true, 
            'data' => $schedules->items(),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'total' => $schedules->total()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'day' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        TeachingSchedule::create($validated);

        return response()->json(['success' => true, 'message' => 'Jadwal ngajar berhasil ditambahkan.']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $schedule = TeachingSchedule::findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'day' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $schedule->update($validated);

        return response()->json(['success' => true, 'message' => 'Jadwal ngajar berhasil diubah.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        TeachingSchedule::destroy($id);
        return response()->json(['success' => true, 'message' => 'Jadwal ngajar dihapus.']);
    }
}
