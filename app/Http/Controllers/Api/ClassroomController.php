<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;

class ClassroomController extends Controller
{
    /**
     * @brief Menampilkan daftar kelas beserta relasi Major, Academic Year, dan Wali Kelas.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $classrooms = Classroom::with(['major', 'academicYear', 'homeroomTeacher'])->latest()->get();

        return response()->json(['success' => true, 'data' => $classrooms]);
    }


    /**
     * @brief Membuat data kelas baru.
     * @param \Illuminate\Http\Request $request (name, grade_level, major_id, academic_year_id, homeroom_teacher_id).
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|in:10,11,12',
            'major_id' => 'required|exists:majors,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id'
        ]);

        Classroom::create($validated);

        return response()->json(['success' => true, 'message' => 'Data Kelas Berhasil dibuat']);
    }


    /**
     * @brief Memperbarui informasi data kelas.
     * @param \Illuminate\Http\Request $request Data update.
     * @param string $id ID Kelas.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $classroom = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|in:10,11,12',
            'major_id' => 'required|exists:majors,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id'
        ]);

        $classroom->update($validated);

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
