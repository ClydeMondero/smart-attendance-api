<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'grade_level',
        'section',
        'teacher',
        'school_year',
        'status',
    ];

    /* ---------------- Relationships ---------------- */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function sessions()
    {
        return $this->hasMany(AttendanceSession::class, 'class_id');
    }
}
