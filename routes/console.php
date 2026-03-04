<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Models\TeachingJournal;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Robot ini bakal jalan setiap hari (daily)
Schedule::call(function () {
    // Cari jurnal yang dibuat lebih dari 7 hari yang lalu
    $oldJournals = TeachingJournal::where('created_at', '<', now()->subDays(7))->get();

    foreach ($oldJournals as $journal) {
        // Hapus fotonya dari storage
        $photos = json_decode($journal->photo_evidence, true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }
        // Hapus datanya dari database
        $journal->delete();
    }
})->daily();