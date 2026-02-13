<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // 1. Izin buat ngisi kolom-kolom ini secara massal (create/update)
    protected $fillable = [
        'teacher_id',
        'date',
        'check_in',
        'check_out',
        'method',
        'status',
        'late_duration',
        'photo_path',    // <--- TAMBAHAN BARU (Path Foto)
        'latitude',      // (Opsional jika pakai)
        'longitude',
    ];

    /**
     * 2. Relasi: Satu Absensi itu MILIK Satu Guru.
     * Jadi nanti kita bisa panggil: $attendance->teacher->name
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
