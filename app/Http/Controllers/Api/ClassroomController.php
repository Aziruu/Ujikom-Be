<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classrooms = Classroom::with(['majors', 'academicYear', 'homeroomTeacher'])->latest()->get();

        return response()->json(['success' => true, 'data' => $classrooms]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|in:10,11,12',
            'major_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'required|exists:teachers,id'
        ]);

        Classroom::create($validated);

        return response()->json(['success' => true, 'message' => 'Data Kelas Berhasil dibuat']);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $classrooms = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|in:10,11,12',
            'major_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'required|exists:teachers,id'
        ]);

        $classrooms->update($validated);

        return response()->json(['success' => true, 'message' => 'Data Kelas Berhasil di Ubah']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Classroom::destroy($id);

        return response()->json(['success' => true, 'message' => 'Data Berhasil di Hapus']);
    }
}
