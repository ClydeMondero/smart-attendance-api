<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentStoreRequest;
use App\Http\Requests\StudentUpdateRequest;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $q = $request->query('q');

        $students = Student::query()
            ->when($q, function ($builder, $term) {
                $builder->where(function ($w) use ($term) {
                    $w->where('barcode', 'like', "%{$term}%")
                        ->orWhere('full_name', 'like', "%{$term}%")
                        ->orWhere('grade_level', 'like', "%{$term}%")
                        ->orWhere('section', 'like', "%{$term}%")
                        ->orWhere('parent_contact', 'like', "%{$term}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($students);
    }

    public function store(StudentStoreRequest $request): JsonResponse
    {
        $student = Student::create($request->validated());
        return response()->json($student, 201);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json($student);
    }

    public function update(StudentUpdateRequest $request, Student $student): JsonResponse
    {
        $student->update($request->validated());
        return response()->json($student);
    }

    public function destroy(Student $student): Response
    {
        $student->delete();
        return response()->noContent();
    }
}
