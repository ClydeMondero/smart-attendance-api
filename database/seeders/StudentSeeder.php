<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $rows = [
            ['barcode' => 'STU-000001', 'full_name' => 'Juan Dela Cruz',    'grade_level' => 'Grade 10', 'section' => 'A',    'parent_contact' => '09171234567', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000002', 'full_name' => 'Maria Santos',      'grade_level' => 'Grade 9',  'section' => 'B',    'parent_contact' => '09187654321', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000003', 'full_name' => 'Pedro Reyes',       'grade_level' => 'Grade 11', 'section' => 'STEM', 'parent_contact' => '09221234567', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000004', 'full_name' => 'Ana Mendoza',       'grade_level' => 'Grade 8',  'section' => 'C',    'parent_contact' => '09181239876', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000005', 'full_name' => 'Carlo Garcia',      'grade_level' => 'Grade 12', 'section' => 'HUMSS', 'parent_contact' => '09194561234', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000006', 'full_name' => 'Angelica Cruz',     'grade_level' => 'Grade 7',  'section' => 'A',    'parent_contact' => '09205557890', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000007', 'full_name' => 'Mark Villanueva',   'grade_level' => 'Grade 10', 'section' => 'B',    'parent_contact' => '09173456789', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000008', 'full_name' => 'Katrina Flores',    'grade_level' => 'Grade 9',  'section' => 'C',    'parent_contact' => '09192345678', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000009', 'full_name' => 'Joshua Navarro',    'grade_level' => 'Grade 11', 'section' => 'ABM',  'parent_contact' => '09175678901', 'created_at' => $now, 'updated_at' => $now],
            ['barcode' => 'STU-000010', 'full_name' => 'Patricia Ramos',    'grade_level' => 'Grade 12', 'section' => 'STEM', 'parent_contact' => '09179876543', 'created_at' => $now, 'updated_at' => $now],
        ];

        // Re-runnable: insert new or update existing by barcode
        Student::upsert(
            $rows,
            ['barcode'], // unique key
            ['full_name', 'grade_level', 'section', 'parent_contact', 'updated_at'] // columns to update
        );
    }
}
