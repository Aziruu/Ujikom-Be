<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%");
        }

        $perPage = $request->query('per_page', 10);
        return response()->json(
            $query->latest()->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
            'nip' => 'nullable|string|unique:teachers,nip',
            'jenis_kelamin' => 'required|in:L,P',
        ]);

        $teacher = Teacher::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'jenis_kelamin' => $request->jenis_kelamin,
            'password' => Hash::make('12345678'),
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'data' => $teacher
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Guru tidak ditemukan'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email,' . $id,
            'nip' => 'nullable|string|unique:teachers,nip,' . $id,
            'jenis_kelamin' => 'required|in:L,P',
        ]);

        $teacher->update($request->only([
            'name',
            'email',
            'nip',
            'jenis_kelamin'
        ]));

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    public function destroy($id)
    {
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Guru tidak ditemukan'], 404);
        }

        $teacher->delete();
        return response()->json(['success' => true]);
    }

    public function updateRfid(Request $request, $id)
    {
        $request->validate([
            'rfid_uid' => 'required|string|unique:teachers,rfid_uid,' . $id
        ]);

        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Guru tidak ditemukan'], 404);
        }

        $teacher->update(['rfid_uid' => $request->rfid_uid]);

        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    public function updateFace(Request $request, $id)
    {
        // Validasi: face_descriptor WAJIB, photo OPSIONAL (tapi sebaiknya dikirim)
        $request->validate([
            'face_descriptor' => 'required',
            'photo'           => 'nullable|string', // String Base64
        ]);

        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        // 1. Simpan Descriptor (Angka Matematis Wajah)
        $teacher->face_descriptor = $request->face_descriptor;

        // 2. Simpan File Foto (Visual Wajah)
        if ($request->has('photo') && !empty($request->photo)) {
            try {
                // Decode Base64 Image
                $image_64 = $request->photo; // format: data:image/jpeg;base64,...

                // Ambil ekstensi (biasanya jpg/png)
                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];

                // Bersihkan string base64
                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
                $image = str_replace($replace, '', $image_64);
                $image = str_replace(' ', '+', $image);

                // Buat nama file unik: teacher_ID_timestamp.jpg
                $imageName = 'teacher_' . $teacher->id . '_' . time() . '.' . $extension;

                // Simpan ke storage/app/public/teacher_photos
                Storage::disk('public')->put('teacher_photos/' . $imageName, base64_decode($image));

                // Update kolom photo_url di database
                $teacher->photo_url = 'teacher_photos/' . $imageName;
            } catch (\Exception $e) {
                // Jika foto gagal simpan, biarkan error log tapi jangan hentikan proses descriptor
                // return response()->json(['message' => 'Gagal upload foto: ' . $e->getMessage()], 500);
            }
        }

        $teacher->save();

        return response()->json([
            'success' => true,
            'message' => 'Wajah & Foto berhasil didaftarkan!',
            'data'    => $teacher
        ], 200);
    }
}
