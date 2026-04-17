<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssessmentCategory;

class AssessmentCategoryController extends Controller
{
    /**
     * @brief Menampilkan daftar kategori penilaian yang aktif.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = AssessmentCategory::where('is_active', true)->get();
        return response()->json([
            'status' => 'success',
            'data' => $categories
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
     * @brief Menambahkan kategori penilaian baru.
     * @param \Illuminate\Http\Request $request (name, description, type).
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string'
        ]);

        $category = AssessmentCategory::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori penilaian berhasil ditambahkan.',
            'data' => $category
        ], 201);
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
     * @brief Memperbarui data kategori penilaian.
     * @param \Illuminate\Http\Request $request (name, is_active).
     * @param string $id ID Kategori.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $category = AssessmentCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori penilaian berhasil diperbarui.',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
