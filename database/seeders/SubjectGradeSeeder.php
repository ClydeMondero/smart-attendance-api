<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Grade;

class SubjectGradeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Seed some subjects linked to existing classes
        $subjects = [
            [
                'name' => 'Mathematics',
                'class_lookup' => ['grade_level' => 'Grade 1', 'section' => 'Class A', 'school_year' => '2024-2025'],
                'status' => 'Active',
            ],
            [
                'name' => 'Science',
                'class_lookup' => ['grade_level' => 'Grade 1', 'section' => 'Class B', 'school_year' => '2024-2025'],
                'status' => 'Active',
            ],
            [
                'name' => 'English',
                'class_lookup' => ['grade_level' => 'Grade 2', 'section' => 'Class A', 'school_year' => '2023-2024'],
                'status' => 'Inactive',
            ],
            [
                'name' => 'Mathematics',
                'class_lookup' => ['grade_level' => 'Grade 2', 'section' => 'Class A', 'school_year' => '2023-2024'],
                'status' => 'Active',
            ],
        ];

        foreach ($subjects as $s) {
            $class = SchoolClass::where($s['class_lookup'])->first();

            if (!$class) {
                continue; // skip if class not found
            }

            Subject::updateOrCreate(
                [
                    'name'     => $s['name'],
                    'class_id' => $class->id, // uniqueness scoped to class
                ],
                [
                    'status'     => $s['status'],
                    'updated_at' => $now,
                ]
            );
        }

        // Seed grades for some students
        $grades = [
            [
                'student_barcode' => 'STU-000001',
                'subject_lookup'  => ['name' => 'Mathematics', 'grade_level' => 'Grade 1', 'section' => 'Class A', 'school_year' => '2024-2025'],
                'grading_period'  => '1st Quarter',
                'score'           => 89.5,
                'remarks'         => 'Good performance',
            ],
            [
                'student_barcode' => 'STU-000002',
                'subject_lookup'  => ['name' => 'Science', 'grade_level' => 'Grade 1', 'section' => 'Class B', 'school_year' => '2024-2025'],
                'grading_period'  => '1st Quarter',
                'score'           => 92,
                'remarks'         => 'Excellent',
            ],
            [
                'student_barcode' => 'STU-000003',
                'subject_lookup'  => ['name' => 'English', 'grade_level' => 'Grade 2', 'section' => 'Class A', 'school_year' => '2023-2024'],
                'grading_period'  => '1st Quarter',
                'score'           => 74,
                'remarks'         => 'Needs improvement',
            ],
        ];

        foreach ($grades as $g) {
            $student = Student::where('barcode', $g['student_barcode'])->first();

            $class = SchoolClass::where([
                'grade_level' => $g['subject_lookup']['grade_level'],
                'section'     => $g['subject_lookup']['section'],
                'school_year' => $g['subject_lookup']['school_year'],
            ])->first();

            $subject = $class
                ? Subject::where('name', $g['subject_lookup']['name'])
                ->where('class_id', $class->id)
                ->first()
                : null;

            if (!$student || !$subject) {
                continue; // skip if missing relation
            }

            Grade::updateOrCreate(
                [
                    'student_id'     => $student->id,
                    'subject_id'     => $subject->id,
                    'grading_period' => $g['grading_period'],
                ],
                [
                    'score'      => $g['score'],
                    'remarks'    => $g['remarks'],
                    'updated_at' => $now,
                ]
            );
        }
    }
}
