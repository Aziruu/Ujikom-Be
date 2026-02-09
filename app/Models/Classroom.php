<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classroom extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    // > Relasi

    // Kelas milik satu Jurusan
    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    // Kelas ada di Tahun Ajaran tertentu
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Kelas punya satu Wali Kelas (Guru)
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }
}
