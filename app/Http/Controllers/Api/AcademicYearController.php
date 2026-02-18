<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;

class AcademicYearController extends Controller
{
    public function index()
    {
        // Urutkan yang aktif paling atas, lalu berdasarkan nama descending
        $data = AcademicYear::orderBy('is_active', 'desc')
                            ->orderBy('name', 'desc')
                            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'years' => 'required|string',
            'semester' => 'required|in:ganjil,genap',
            'is_active' => 'boolean'
        ]);

        // Jika user mengaktifkan tahun ini, matikan tahun yang lain
        if ($request->is_active) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        $academicYear = AcademicYear::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil ditambahkan.',
            'data' => $academicYear
        ]);
    }

    public function update(Request $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'years' => 'required|string',
            'semester' => 'required|in:ganjil,genap',
            'is_active' => 'boolean'
        ]);

        // Logika switch active
        if ($request->is_active) {
            AcademicYear::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $academicYear->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil diperbarui.',
            'data' => $academicYear
        ]);
    }

    public function destroy($id)
    {
        AcademicYear::destroy($id);
        return response()->json(['success' => true, 'message' => 'Data dihapus.']);
    }
}
