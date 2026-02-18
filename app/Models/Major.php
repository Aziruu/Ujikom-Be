<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'head_of_program_id'
    ];

    // Jurusan punya Kaprog (Guru)
    public function headOfProgram()
    {
        return $this->belongsTo(Teacher::class, 'head_of_program_id');
    }
}
