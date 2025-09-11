<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\Student;

class ClassStudentSeeder extends Seeder
{
    public function run(): void
    {
        // data set
        $classes = [
            [
                'grade_level' => 'Grade 1',
                'section'     => 'Class A',
                'teacher'     => 'Mr. Smith',
                'school_year' => '2024-2025',
                'status'      => 'active',
                'students'    => [
                    ['barcode' => 'STU-000001', 'full_name' => 'John Doe',   'parent_contact' => '09171234567'],
                    ['barcode' => 'STU-000002', 'full_name' => 'Jane Smith', 'parent_contact' => '09179876543'],
                ],
            ],
            [
                'grade_level' => 'Grade 1',
                'section'     => 'Class B',
                'teacher'     => 'Ms. Johnson',
                'school_year' => '2024-2025',
                'status'      => 'active',
                'students'    => [
                    ['barcode' => 'STU-000003', 'full_name' => 'Carlos Reyes', 'parent_contact' => '09175678901'],
                    ['barcode' => 'STU-000004', 'full_name' => 'Maria Cruz',   'parent_contact' => '09172345678'],
                ],
            ],
            [
                'grade_level' => 'Grade 2',
                'section'     => 'Class A',
                'teacher'     => 'Mrs. Lee',
                'school_year' => '2023-2024',
                'status'      => 'inactive',
                'students'    => [
                    ['barcode' => 'STU-000005', 'full_name' => 'David Lim',  'parent_contact' => '09173456789'],
                    ['barcode' => 'STU-000006', 'full_name' => 'Angela Tan', 'parent_contact' => '09205557890'],
                ],
            ],
        ];

        DB::transaction(function () use ($classes) {
            foreach ($classes as $c) {
                // Upsert class by unique tuple
                $class = SchoolClass::updateOrCreate(
                    [
                        'grade_level' => $c['grade_level'],
                        'section'     => $c['section'],
                        'school_year' => $c['school_year'],
                    ],
                    [
                        'teacher' => $c['teacher'],
                        'status'  => $c['status'],
                    ]
                );

                foreach ($c['students'] as $s) {
                    // Upsert student by unique barcode
                    Student::updateOrCreate(
                        ['barcode' => $s['barcode']], // unique key
                        [
                            'full_name'      => $s['full_name'],
                            'grade_level'    => $c['grade_level'],
                            'section'        => $c['section'],
                            'parent_contact' => $s['parent_contact'],
                            'class_id'       => $class->id,
                        ]
                    );
                }
            }
        });
    }
}
