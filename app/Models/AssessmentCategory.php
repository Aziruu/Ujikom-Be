<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentCategory extends Model
{
    protected $guarded = ['id'];

    public function assessmentDetails()
    {
        return $this->hasMany(AssessmentDetail::class, 'category_id');
    }
}
