<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with('schoolClass');

        if ($request->user()->role === 'teacher') {
            $teacher = $request->user()->name;
            $query->whereHas('schoolClass', function ($q) use ($teacher) {
                $q->where('teacher', $teacher);
            });
        }

        return response()->json($query->get());
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('subjects')->where(
                    fn($q) =>
                    $q->where('class_id', $request->class_id)
                ),
            ],
            'class_id' => 'required|exists:classes,id',
            'status' => 'required|in:Active,Inactive',
        ], [
            'name.unique' => 'This subject already exists in the selected class.',
        ]);

        $subject = Subject::create($validated);

        return response()->json($subject, 201);
    }

    public function show($id)
    {
        return response()->json(Subject::with('schoolClass')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                Rule::unique('subjects')->where(
                    fn($q) =>
                    $q->where('class_id', $request->class_id ?? $subject->class_id)
                )->ignore($subject->id),
            ],
            'class_id' => 'sometimes|exists:classes,id',
            'status' => 'sometimes|in:Active,Inactive',
        ], [
            'name.unique' => 'This subject already exists in the selected class.',
        ]);

        $subject->update($validated);

        return response()->json($subject);
    }

    public function destroy($id)
    {
        Subject::findOrFail($id)->delete();

        return response()->json(['message' => 'Subject deleted successfully']);
    }
}
