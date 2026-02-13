<?php

namespace App\Http\Controllers\Api;

use App\Models\LeaveRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Guru mengajukan cuti
     * Status default: pending
     */
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string',
            'type'       => 'required|in:sakit,izin',
            'file'       => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('leave_attachments', 'public');
        }

        $leave = LeaveRequest::create([
            'teacher_id' => $request->teacher_id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $request->reason,
            'type'       => $request->type,
            'status'     => 'pending',
            'attachment' => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan Berhasil Dikirm!',
            'data' => $leave,
        ], 201);
    }

    /**
     * Admin melihat daftar pengajuan cuti
     * Bisa difilter status
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with('teacher')->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(10));
    }

    /**
     * Admin approve / reject cuti
     */
    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string'
        ]);

        $leave = LeaveRequest::find($id);
        if (!$leave) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $leave->update($request->only('status', 'admin_note'));

        return response()->json([
            'success' => true,
            'message' => "Pengajuan cuti berhasil di-{$request->status}."
        ]);
    }
}
