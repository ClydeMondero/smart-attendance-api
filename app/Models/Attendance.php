<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'grade_level',
        'section',
        'teacher',
        'school_year',
        'status',
        'log_date',
        'student_name',
        'student_id',
        'time_in',
        'time_out',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    // Scopes for convenience
    public function scopeClasses($query)
    {
        return $query->where('type', 'class');
    }

    public function scopeEntries($query)
    {
        return $query->where('type', 'entry');
    }
}
