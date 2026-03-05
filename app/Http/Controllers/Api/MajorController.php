<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
use Illuminate\Http\Request;

class MajorController extends Controller
{
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

    public function destroy($id)
    {
        Major::destroy($id);
        return response()->json(['success' => true, 'message' => 'Jurusan dihapus.']);
    }
}
