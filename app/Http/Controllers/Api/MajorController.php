<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    public function index()
    {
        $majors = Major::with('headOfProgram')->latest()->get();
        return response()->json(['success' => true, 'data' => $majors]);
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
            'code' => 'required|unique:majors,code,'.$id,
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