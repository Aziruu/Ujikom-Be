<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $guarded = ['id'];

    // Relasi ke User sebagai Penilai
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // Relasi ke Teacher sebagai yang Dinilai
    public function evaluatee()
    {
        return $this->belongsTo(Teacher::class, 'evaluatee_id');
    }

    // Relasi ke Detail Penilaian (Item-item skor)
    public function details()
    {
        return $this->hasMany(AssessmentDetail::class, 'assessment_id');
    }
}
