<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTokens extends Model
{
    protected $guarded = ['id'];

    public function item()
    {
        return $this->belongsTo(FlexibilityItems::class, 'item_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
