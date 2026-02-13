<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Teacher extends Model
{
    use HasApiTokens, HasFactory;

    protected $guarded = ['id'];

    //  Function Logic UUID
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->guru_id)) {
                $model->guru_id = (string) Str::uuid();
            }
        });
    }

    //  > Relasi

    // Guru punya banyak Absensi
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Guru punya banyak Jurnal Mengajar
    public function journals()
    {
        return $this->hasMany(TeachingJournal::class);
    }

    // Guru punya banyak Jadwal Mengajar
    public function schedules()
    {
        return $this->hasMany(TeachingSchedule::class);
    }

    // Guru jadi Wali Kelas (Cuma 1 kelas)
    public function homeroomClass()
    {
        return $this->hasOne(Classroom::class, 'homeroom_teacher_id');
    }

    // Relasi: Guru punya banyak Pengajuan Cuti
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
