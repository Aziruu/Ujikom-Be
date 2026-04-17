<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    /**
     * @brief Menampilkan daftar jurusan.
     * @details Mendukung fitur pencarian berdasarkan nama atau kode jurusan serta pagination.
     * @param \Illuminate\Http\Request $request (search)
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Major::with('headOfProgram');

        // Fitur pencarian berdasarkan kode atau nama jurusan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $majors = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $majors->items(),
            'meta' => [
                'current_page' => $majors->currentPage(),
                'last_page' => $majors->lastPage(),
                'total' => $majors->total()
            ]
        ]);
    }

    /**
     * @brief Menyimpan data jurusan baru.
     * @param \Illuminate\Http\Request $request (code, name, head_of_program_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:majors,code',
            'name' => 'required',
            'head_of_program_id' => 'nullable|exists:teachers,id'
        ]);

        Major::create($validated);

        return response()->json(['success' => true, 'message' => 'Jurusan berhasil dibuat.']);
    }

    /**
     * @brief Memperbarui data jurusan.
     * @param \Illuminate\Http\Request $request Data update.
     * @param int $id ID Jurusan.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $major = Major::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|unique:majors,code,' . $id,
            'name' => 'required',
            'head_of_program_id' => 'nullable|exists:teachers,id'
        ]);

        $major->update($validated);

        return response()->json(['success' => true, 'message' => 'Jurusan berhasil diupdate.']);
    }

    /**
     * @brief Menghapus data jurusan.
     * @param int $id ID Jurusan.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        Major::destroy($id);
        return response()->json(['success' => true, 'message' => 'Jurusan dihapus.']);
    }
}
