<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['allow_grades', 'school_in_template', 'school_out_template', 'class_in_template', 'class_out_template'];
}
