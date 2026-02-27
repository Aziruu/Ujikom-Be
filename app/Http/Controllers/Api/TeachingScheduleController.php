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
    public function index()
    {
        $schedules = TeachingSchedule::with(['teacher', 'classroom', 'subject'])
            ->orderBy('start_time')
            ->get();

        $sortedSchedules = $schedules->sortBy(function ($schedule) {
            $days = [
                'senin' => 1,
                'selasa' => 2,
                'rabu' => 3,
                'kamis' => 4,
                'jumat' => 5,
                'sabtu' => 6,
                'minggu' => 7
            ];
            return $days[$schedule->day] ?? 99;
        })->values();

        return response()->json(['success' => true, 'data' => $sortedSchedules]);
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
