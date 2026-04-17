<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FlexibilityItems;
use App\Models\Teacher;
use App\Models\PointLedger;
use App\Models\PointLedgers;
use App\Models\UserToken;
use App\Models\UserTokens;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MarketplaceController extends Controller
{
    // =====================================================================
    // BAGIAN 1: ENDPOINT UNTUK MOBILE APP (USER)
    // =====================================================================

    /**
     * @brief Menampilkan katalog item sekaligus saldo poin user.
     */
    public function index(Request $request)
    {
        // Hanya ambil item yang sedang dijual (is_active = true)
        $items = FlexibilityItems::where('is_active', true)->latest()->get();

        $data = [
            'items' => $items,
            'user_points' => 0 // Default
        ];

        // Jika mobile mengirimkan ID guru, kita sekalian balikin sisa uang/poinnya
        if ($request->filled('teacher_id')) {
            $teacher = Teacher::select('id', 'point_balance')->find($request->teacher_id);
            if ($teacher) {
                $data['user_points'] = $teacher->point_balance;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @brief Membeli token kelonggaran menggunakan poin integritas.
     */
    public function buyToken(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'item_id'    => 'required|exists:flexibility_items,id',
        ]);

        // Mulai transaksi database agar saldo aman
        DB::beginTransaction();
        try {
            // Gunakan lockForUpdate untuk mencegah double-spend (beli barengan)
            $teacher = Teacher::where('id', $request->teacher_id)->lockForUpdate()->first();
            $item = FlexibilityItems::where('id', $request->item_id)->where('is_active', true)->first();

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Item tidak ditemukan atau tidak aktif.'], 404);
            }

            // Validasi Saldo Poin
            if ($teacher->point_balance < $item->point_cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Poin kamu tidak cukup! Rajin-rajin datang pagi dulu sana!'
                ], 400);
            }

            // 1. Kurangi Saldo Guru
            $newBalance = $teacher->point_balance - $item->point_cost;
            $teacher->point_balance = $newBalance;
            $teacher->save();

            // 2. Catat Mutasi Keluar di Ledger (Buku Besar)
            PointLedgers::create([
                'teacher_id'       => $teacher->id,
                'transaction_type' => 'SPEND',
                'amount'           => -$item->point_cost, // Minus karena pengeluaran
                'current_balance'  => $newBalance,
                'description'      => "Membeli token: " . $item->item_name
            ]);

            // 3. Masukkan Token ke Inventaris (Tas) Guru
            $token = UserTokens::create([
                'teacher_id' => $teacher->id,
                'item_id'    => $item->id,
                'status'     => 'AVAILABLE'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil! Token masuk ke inventaris.',
                'data' => [
                    'sisa_poin' => $newBalance,
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat transaksi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =====================================================================
    // BAGIAN 2: ENDPOINT UNTUK WEB ADMIN (MANAJEMEN TOKO)
    // =====================================================================

    /**
     * @brief Menampilkan SEMUA item untuk Admin (termasuk yang dinonaktifkan).
     */
    public function adminIndex()
    {
        $items = FlexibilityItems::latest()->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * @brief Admin menambah item baru ke katalog.
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_name'   => 'required|string|max:255',
            'description' => 'nullable|string',
            'point_cost'  => 'required|integer|min:1',
            'stock_limit' => 'nullable|integer|min:1',
            'image'       => 'nullable|string' // Format Base64
        ]);

        $imageUrl = null;

        if ($request->filled('image')) {
            try {
                $image_64 = $request->image;
                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
                $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
                $image = str_replace($replace, '', $image_64);
                $image = str_replace(' ', '+', $image);

                $imageName = 'item_' . time() . '.' . $extension;
                Storage::disk('public')->put('marketplace/' . $imageName, base64_decode($image));
                $imageUrl = 'marketplace/' . $imageName;
            } catch (\Exception $e) {
                // Abaikan jika error
            }
        }

        $item = FlexibilityItems::create([
            'item_name'   => $request->item_name,
            'description' => $request->description,
            'point_cost'  => $request->point_cost,
            'stock_limit' => $request->stock_limit,
            'image_url'   => $imageUrl,
            'is_active'   => $request->boolean('is_active', true)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Yey! Barang dagangan baru berhasil ditambahkan.',
            'data'    => $item
        ], 201);
    }

    /**
     * @brief Admin mengedit item yang ada.
     */
    public function update(Request $request, $id)
    {
        $item = FlexibilityItems::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
        }

        $request->validate([
            'item_name'  => 'required|string|max:255',
            'point_cost' => 'required|integer|min:1',
        ]);

        $item->update($request->only([
            'item_name',
            'description',
            'point_cost',
            'stock_limit',
            'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil diperbarui.',
            'data'    => $item
        ]);
    }

    /**
     * @brief Admin menghapus/menonaktifkan item.
     */
    public function destroy($id)
    {
        $item = FlexibilityItems::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
        }

        // Praktik terbaik: Jangan hapus datanya agar history Ledger tidak rusak.
        $item->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Item telah ditarik dari peredaran.'
        ]);
    }
}
