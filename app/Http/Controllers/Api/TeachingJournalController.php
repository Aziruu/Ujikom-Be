<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeachingJournal;
use Illuminate\Http\Request;

class TeachingJournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $journals = TeachingJournal::with(['teacher', 'classroom', 'schedule'])->latest()->get();
        return response()->json(['success' => true, 'data' => $journals]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
