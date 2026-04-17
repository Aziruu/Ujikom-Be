<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;

class TeachingJournalController extends Controller
{
    /**
     * @brief Menampilkan daftar jurnal mengajar yang sudah dikirim.
     * @details Dilengkapi filter pencarian berdasarkan nama guru atau kelas.
     * @param \Illuminate\Http\Request $request (search)
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = TeachingJournal::with(['teacher', 'classroom', 'schedule']);

        // Fitur pencarian berdasarkan nama guru atau nama kelas
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('classroom', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $journals = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $journals->items(),
            'meta' => [
                'current_page' => $journals->currentPage(),
                'last_page' => $journals->lastPage(),
                'total' => $journals->total()
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @brief Menyimpan jurnal kegiatan belajar mengajar (KBM).
     * @details Menangani unggahan hingga 3 foto bukti kegiatan dan koordinat lokasi saat submit.
     * @param \Illuminate\Http\Request $request (teacher_id, classroom_id, date, topic, photos[], latitude, longitude)
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'schedule_id' => 'nullable|exists:teaching_schedules,id',
            'date' => 'required|date',
            'topic' => 'required|string|max:255',
            'photos' => 'required|array|min:1|max:3',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $photoPaths = [];

        // Proses simpan fotonya ke folder public/journals
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('journals', 'public');
                $photoPaths[] = $path;
            }
        }

        // Simpan ke database
        $journal = TeachingJournal::create([
            'teacher_id' => $request->teacher_id,
            'classroom_id' => $request->classroom_id,
            'schedule_id' => $request->schedule_id,
            'date' => $request->date,
            'topic' => $request->topic,
            'photo_evidence' => json_encode($photoPaths),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'menunggu',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jurnal mengajar berhasil dikirim!',
            'data' => $journal
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * @brief Menghapus jurnal mengajar beserta file foto fisiknya dari storage.
     * @param string $id ID Jurnal.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $journal = TeachingJournal::findOrFail($id);

        // Hapus file foto fisiknya dulu dari folder public
        $photos = json_decode($journal->photo_evidence, true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($photo);
            }
        }

        // Baru hapus datanya dari database
        $journal->delete();

        return response()->json(['success' => true, 'message' => 'Jurnal dan fotonya berhasil dihapus!']);
    }

    // Fungsi khusus buat ACC / Tolak Jurnal
    /**
     * @brief Verifikasi validitas jurnal mengajar oleh Admin.
     * @param \Illuminate\Http\Request $request (status: valid/ditolak)
     * @param int $id ID Jurnal.
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:valid,ditolak']);
        $journal = TeachingJournal::findOrFail($id);

        $journal->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status jurnal berhasil diubah!']);
    }
}
