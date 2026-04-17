<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\PointLedgers;
use App\Models\UserTokens;

class WalletController extends Controller
{
    /**
     * @brief Mengambil semua data untuk halaman Dompet Integritas User (Mobile)
     */
    public function getMyWallet(Request $request)
    {
        // Asumsi kamu mengirimkan teacher_id, atau kalau pakai token Sanctum bisa pakai $request->user()->id
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id'
        ]);

        $teacherId = $request->teacher_id;
        $teacher = Teacher::select('id', 'name', 'point_balance')->find($teacherId);

        // 1. Logika untuk Level (Hero Section)
        // Bebas kamu atur rank-nya sesuai poin
        $level = 'Disiplin Pemula';
        if ($teacher->point_balance > 100) $level = 'Disiplin Menengah';
        if ($teacher->point_balance > 300) $level = 'Disiplin Elite';
        if ($teacher->point_balance > 500) $level = 'Sultan Integritas 👑';
        if ($teacher->point_balance < 0) $level = 'Fakir Integritas 📉';

        // 2. Data Tab 1: Riwayat Mutasi (Ledger)
        // Ambil 20 transaksi terakhir agar tidak berat
        $mutasi = PointLedgers::where('teacher_id', $teacherId)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // 3. Data Tab 3: My Inventory (Voucher yang dimiliki)
        // Gunakan 'with' untuk me-load relasi data detail item/voucher-nya
        $inventory = UserTokens::with('item') 
            ->where('teacher_id', $teacherId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => [
                    'poin' => $teacher->point_balance,
                    'level' => $level,
                ],
                'tab_mutasi' => $mutasi,
                'tab_inventory' => $inventory->map(function ($token) {
                    return [
                        'id' => $token->id,
                        'status' => $token->status, // AVAILABLE, USED, EXPIRED
                        'used_at' => $token->used_at,
                        'voucher_name' => $token->item->item_name ?? 'Item Tidak Diketahui',
                        'description' => $token->item->description ?? '',
                        'image_url' => $token->item->image_url ?? null,
                    ];
                })
            ]
        ]);
    }
}