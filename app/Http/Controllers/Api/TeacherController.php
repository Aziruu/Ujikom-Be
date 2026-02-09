<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    // 1. LIHAT SEMUA GURU
    public function index()
    {
        $teachers = Teacher::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $teachers]);
    }

    // 2. TAMBAH GURU BARU
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:teachers,email',
            'nip' => 'nullable|unique:teachers,nip',
            'jenis_kelamin' => 'required|in:L,P',
        ]);

        $teacher = Teacher::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'jenis_kelamin' => $request->jenis_kelamin,
            'password' => Hash::make('12345678'), // Password default
            'is_active' => true
        ]);

        return response()->json(['success' => true, 'message' => 'Guru berhasil ditambahkan', 'data' => $teacher]);
    }

    // 3. EDIT GURU
    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);
        if (!$teacher) return response()->json(['message' => 'Guru tidak ditemukan'], 404);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:teachers,email,' . $id, // Boleh email sama kalau punya sendiri
            'jenis_kelamin' => 'required|in:L,P',
        ]);

        $teacher->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'jenis_kelamin' => $request->jenis_kelamin,
        ]);

        return response()->json(['success' => true, 'message' => 'Data guru berhasil diupdate']);
    }

    // 4. HAPUS GURU
    public function destroy($id)
    {
        $teacher = Teacher::find($id);
        if (!$teacher) return response()->json(['message' => 'Guru tidak ditemukan'], 404);

        $teacher->delete();
        return response()->json(['success' => true, 'message' => 'Guru berhasil dihapus']);
    }

    // Update RFID UID untuk Guru tertentu
    /**
     * Update khusus untuk RFID UID saja.
     */
    public function updateRfid(Request $request, $id)
    {
        // 1. Validasi input
        $request->validate([
            'rfid_uid' => 'required|string|unique:teachers,rfid_uid,' . $id
        ]);

        // 2. Cari gurunya
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        // 3. Update datanya
        $teacher->rfid_uid = $request->rfid_uid;
        $teacher->save();

        return response()->json([
            'success' => true,
            'message' => 'RFID berhasil ditautkan!',
            'data'    => $teacher
        ], 200);
    }
}
