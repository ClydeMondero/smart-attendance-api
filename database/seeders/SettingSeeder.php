<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->insert([
            'allow_grades' => true,
            'school_in_template' => "Good day, {{student_parent_name}}!

We’re reaching out to inform you that your child, {{student_full_name}}, has entered the school at  {{time_in}}.

This message is part of our ongoing effort to ensure transparency and your child's safety while on school grounds. If you have any concerns or questions, feel free to reach out to our administrative office.

Thank you for your continued support and trust in Smart Attendance.

Warm regards,
Smart Attendance Admin Team",
            'school_out_template' => "Good day, {{student_parent_name}}!

We’re reaching out to inform you that your child, {{student_full_name}}, has exited the school at  {{time_out}}.

This message is part of our ongoing effort to ensure transparency and your child's safety while on school grounds. If you have any concerns or questions, feel free to reach out to our administrative office.

Thank you for your continued support and trust in Smart Attendance.

Warm regards,
Smart Attendance Admin Team",
            'class_in_template' => "Good day, {{student_parent_name}}!

We’re reaching out to inform you that your child, {{student_full_name}}, has started attending his class at {{time_in}}.

This message is part of our ongoing effort to ensure transparency and your child's safety while on school grounds. If you have any concerns or questions, feel free to reach out to our administrative office.

Thank you for your continued support and trust in Smart Attendance.

Warm regards,
Smart Attendance Admin Team",
            'class_out_template' => "Good day, {{student_parent_name}}!

We’re reaching out to inform you that your child, {{student_full_name}}, has finished attending his classes at {{time_out}}.

This message is part of our ongoing effort to ensure transparency and your child's safety while on school grounds. If you have any concerns or questions, feel free to reach out to our administrative office.

Thank you for your continued support and trust in Smart Attendance.

Warm regards,
Smart Attendance Admin Team",
        ]);
    }
}
