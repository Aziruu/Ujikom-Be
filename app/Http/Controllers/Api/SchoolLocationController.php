<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolLocation;
use Illuminate\Http\Request;

class SchoolLocationController extends Controller
{
    /**
     * @brief Menampilkan semua titik lokasi kampus yang terdaftar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $locations = SchoolLocation::latest()->get();
        return response()->json(['success' => true, 'data' => $locations]);
    }

    /**
     * @brief Menambahkan titik lokasi kampus baru (Geofencing).
     * @details Menentukan koordinat pusat dan radius jangkauan untuk keperluan absensi.
     * @param \Illuminate\Http\Request $request (name, latitude, longitude, radius)
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'radius' => 'required|integer|min:10', // Minimal 10 meter
        ]);

        $location = SchoolLocation::create($validated);
        return response()->json(['success' => true, 'message' => 'Lokasi kampus berhasil ditambahkan.', 'data' => $location], 201);
    }

    /**
     * @brief Memperbarui koordinat atau radius lokasi kampus.
     * @param \Illuminate\Http\Request $request Data update.
     * @param int $id ID Lokasi.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $location = SchoolLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'radius' => 'required|integer|min:10',
        ]);

        $location->update($validated);
        return response()->json(['success' => true, 'message' => 'Lokasi kampus berhasil diperbarui.']);
    }

    public function destroy($id)
    {
        SchoolLocation::destroy($id);
        return response()->json(['success' => true, 'message' => 'Lokasi kampus dihapus.']);
    }
}
