<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        return response()->json(Grade::with(['student', 'subject'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'grading_period' => 'required|string',
            'score' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
        ]);

        $grade = Grade::create($validated);

        return response()->json($grade, 201);
    }

    public function show($id)
    {
        return response()->json(Grade::with(['student', 'subject'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'grading_period' => 'sometimes|string',
            'score' => 'sometimes|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
        ]);

        $grade->update($validated);

        return response()->json($grade);
    }

    public function destroy($id)
    {
        Grade::findOrFail($id)->delete();

        return response()->json(['message' => 'Grade deleted successfully']);
    }
}
