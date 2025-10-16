<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'type',        // 'class' | 'entry'
        'class_id',    // nullable for gate entry
        'student_id',  // nullable for gate entry
        'subject_id',
        'log_date',
        'status',      // present|late|absent|excused|in|out
        'time_in',
        'time_out',
        'note',
    ];

    protected $casts = [
        'log_date' => 'date',
        'time_in'  => 'datetime:H:i:s',
        'time_out' => 'datetime:H:i:s',
    ];

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(\App\Models\SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
