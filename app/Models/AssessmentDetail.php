<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentDetail extends Model
{
    protected $guarded = ['id'];

    // Relasi balik ke header Penilaian
    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    // Relasi ke Master Kategori
    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}
