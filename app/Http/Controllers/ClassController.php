<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/classes
     */
    public function index(Request $request)
    {
        $q = SchoolClass::query()
            ->when($request->q, function ($query, $search) {
                $query->where('section', 'like', "%$search%")
                    ->orWhere('teacher', 'like', "%$search%");
            })
            ->when($request->grade_level, fn($query, $gl) => $query->where('grade_level', $gl))
            ->when($request->status, fn($query, $st) => $query->where('status', strtolower($st)))
            ->when($request->teacher, fn($query, $teacher) => $query->where('teacher', $teacher));

        return $q->orderBy('grade_level')
            ->orderBy('section')
            ->paginate($request->integer('per_page', 20));
    }


    /**
     * Store a newly created resource.
     * POST /api/classes
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'grade_level' => ['required', 'string', 'max:50'],
            'section'     => ['required', 'string', 'max:50'],
            'teacher'     => ['nullable', 'string', 'max:191'],
            'school_year' => ['required', 'string', 'max:20'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $class = SchoolClass::create($data);

        return response()->json($class, 201);
    }

    /**
     * Display the specified resource.
     * GET /api/classes/{class}
     */
    public function show(SchoolClass $class)
    {
        return $class->loadCount('students');
    }

    /**
     * Update the specified resource.
     * PUT /api/classes/{class}
     */
    public function update(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'grade_level' => ['sometimes', 'string', 'max:50'],
            'section'     => ['sometimes', 'string', 'max:50'],
            'teacher'     => ['nullable', 'string', 'max:191'],
            'school_year' => ['sometimes', 'string', 'max:20'],
            'status'      => [Rule::in(['active', 'inactive'])],
        ]);

        $class->update($data);

        return $class->fresh();
    }

    /**
     * Remove the specified resource.
     * DELETE /api/classes/{class}
     */
    public function destroy(SchoolClass $class)
    {
        $class->delete();

        return response()->noContent();
    }
}
