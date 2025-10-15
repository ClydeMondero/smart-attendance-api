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
        $classes = [
            [
                'grade_level' => 'Grade 1',
                'section'     => 'Class A',
                'teacher'     => 'Angela Smith',
                'school_year' => '2024-2025',
                'status'      => 'active',
                'students'    => [
                    ['barcode' => 'STU-000001', 'full_name' => 'John Doe', 'parent_contact' => '09171234567', 'parent_name' => 'John Doe Sr.', 'is_active' => true],
                    ['barcode' => 'STU-000002', 'full_name' => 'Jane Smith', 'parent_contact' => '09179876543', 'parent_name' => 'Jane Doe', 'is_active' => true],
                ],
            ],
            [
                'grade_level' => 'Grade 1',
                'section'     => 'Class B',
                'teacher'     => 'John Johnson',
                'school_year' => '2024-2025',
                'status'      => 'active',
                'students'    => [
                    ['barcode' => 'STU-000003', 'full_name' => 'Carlos Reyes', 'parent_contact' => '09175678901', 'parent_name' => 'Carlos Reyes Sr.', 'is_active' => true],
                    ['barcode' => 'STU-000004', 'full_name' => 'Maria Cruz', 'parent_contact' => '09172345678', 'parent_name' => 'Maria Cruz', 'is_active' => true],
                ],
            ],
            [
                'grade_level' => 'Grade 2',
                'section'     => 'Class A',
                'teacher'     => 'Kim Lee',
                'school_year' => '2023-2024',
                'status'      => 'inactive',
                'students'    => [
                    ['barcode' => 'STU-000005', 'full_name' => 'David Lim', 'parent_contact' => '09173456789', 'parent_name' => 'David Lim Sr.', 'is_active' => true],
                    ['barcode' => 'STU-000006', 'full_name' => 'Angela Tan', 'parent_contact' => '09205557890', 'parent_name' => 'Angela Tan', 'is_active' => true],
                ],
            ],
        ];

        DB::transaction(function () use ($classes) {
            foreach ($classes as $c) {
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
                    Student::updateOrCreate(
                        ['barcode' => $s['barcode']], // unique key
                        [
                            'full_name'      => $s['full_name'],
                            'parent_contact' => $s['parent_contact'],
                            'parent_name'     => $s['parent_name'],
                            'class_id'       => $class->id,
                            'is_active'      => $s['is_active'],
                        ]
                    );
                }
            }
        });
    }
}
