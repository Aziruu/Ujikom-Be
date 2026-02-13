<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    // Field yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'teacher_id',
        'start_date',
        'end_date',
        'type',
        'reason',
        'status',
        'admin_note',
        'attachment'
    ];

    // Relasi: Satu Cuti dimiliki oleh Satu Guru
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}