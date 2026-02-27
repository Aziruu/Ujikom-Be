<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public function index()
    {
        $schedules = WorkSchedule::all();

        if ($schedules->isEmpty()) {
            $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
            foreach ($days as $day) {
                WorkSchedule::create([
                    'day_name' => $day,
                    'start_time' => '07:00:00',
                    'end_time' => '15:00:00',
                    'late_tolerance' => 15,
                    'is_holiday' => ($day === 'sabtu' || $day === 'minggu') ? true : false,
                ]);
            }
            $schedules = WorkSchedule::all(); // Tarik lagi datanya setelah dibuat
        }

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    public function update(Request $request, $id)
    {
        $schedule = WorkSchedule::findOrFail($id);

        $validated = $request->validate([
            'start_time' => 'required',
            'end_time' => 'required',
            'late_tolerance' => 'required|integer',
            'is_holiday' => 'boolean'
        ]);

        $schedule->update($validated);

        return response()->json(['success' => true, 'message' => 'Jadwal kerja berhasil diupdate.']);
    }
}