<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PointRules;

class PointRuleController extends Controller
{
    public function index()
    {
        $rules = PointRules::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $rules
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rule_name'          => 'required|string|max:255',
            'condition_operator' => 'required|in:<,>,<=,>=,=,BETWEEN',
            'condition_value'    => 'required|string',
            'point_modifier'     => 'required|integer',
        ]);

        $rule = PointRules::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Aturan integritas berhasil ditambahkan!',
            'data'    => $rule
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $rule = PointRules::find($id);
        if (!$rule) return response()->json(['message' => 'Aturan tidak ditemukan'], 404);

        $request->validate([
            'rule_name'          => 'required|string|max:255',
            'condition_operator' => 'required|in:<,>,<=,>=,=,BETWEEN',
            'condition_value'    => 'required|string',
            'point_modifier'     => 'required|integer',
        ]);

        $rule->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Aturan berhasil diperbarui!',
            'data'    => $rule
        ]);
    }

    public function destroy($id)
    {
        $rule = PointRules::find($id);
        if (!$rule) return response()->json(['message' => 'Aturan tidak ditemukan'], 404);

        // PROTEKSI: Rule Alpa tidak boleh disentuh!
        if (stripos($rule->rule_name, 'alpa') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Aturan Alpa adalah aturan sistem wajib. Tidak boleh dinonaktifkan, Sayang!'
            ], 403);
        }

        // Menonaktifkan saja agar history aman
        $rule->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Aturan berhasil dinonaktifkan.'
        ]);
    }
}
