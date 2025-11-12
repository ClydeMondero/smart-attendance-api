<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Services\TextBeeService;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $query = Grade::with(['student', 'subject']);

        // Filter by student_id if provided
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter for teacher
        if ($request->user()->role === 'teacher') {
            $teacher = $request->user()->name; // or use id if stored
            $query->whereHas('subject.schoolClass', fn($q) => $q->where('teacher', $teacher))
                ->whereHas('student.schoolClass', fn($q) => $q->where('teacher', $teacher));
        }

        return response()->json($query->get());
    }


    public function store(Request $request, TextBeeService $textbee)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'grading_period' => 'required|string',
            'score' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
        ]);

        $grade = Grade::create($validated);

        // Send SMS to parent
        $student = $grade->student;
        $message = "New grade recorded for {$student->full_name}: {$grade->score} in {$grade->subject->name} ({$grade->grading_period}). Remarks: {$grade->remarks}";
        $textbee->sendSms($student->parent_contact, $message);

        return response()->json($grade, 201);
    }

    public function show($id)
    {
        return response()->json(Grade::with(['student', 'subject'])->findOrFail($id));
    }

    public function update(Request $request, $id, TextBeeService $textbee)
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

        // Send SMS to parent
        $student = $grade->student;
        $message = "Grade updated for {$student->name}: {$grade->score} in {$grade->subject->name} ({$grade->grading_period}). Remarks: {$grade->remarks}";
        $textbee->sendSms($student->parent_contact, $message);

        return response()->json($grade);
    }

    public function destroy($id)
    {
        Grade::findOrFail($id)->delete();

        return response()->json(['message' => 'Grade deleted successfully']);
    }
}
