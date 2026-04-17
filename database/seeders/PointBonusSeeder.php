<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\PointLedgers;
use Illuminate\Support\Facades\DB;

class PointBonusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua data guru yang aktif
        $teachers = Teacher::where('is_active', true)->get();

        if ($teachers->isEmpty()) {
            $this->command->info('Belum ada guru yang terdaftar nih!');
            return;
        }

        $this->command->info('Memproses bonus 100 poin untuk semua guru...');

        foreach ($teachers as $teacher) {
            // Bungkus dalam transaction agar data sinkron antara teacher dan ledger
            DB::transaction(function () use ($teacher) {
                $bonusAmount = 100;
                $newBalance = $teacher->point_balance + $bonusAmount;

                // 1. Catat ke Buku Besar (Ledger)
                PointLedgers::create([
                    'teacher_id'       => $teacher->id,
                    'transaction_type' => 'EARN',
                    'amount'           => $bonusAmount,
                    'current_balance'  => $newBalance,
                    'description'      => 'Bonus Poin Perdana dari Sistem! 🎉',
                ]);

                // 2. Update saldo cache di tabel teachers
                $teacher->update([
                    'point_balance' => $newBalance
                ]);
            });
        }

        $this->command->info('Selesai! Semua guru sudah jadi orang kaya baru dengan 100 poin. 🪙✨');
    }
}