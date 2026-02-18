<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'years',
        'semester',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
