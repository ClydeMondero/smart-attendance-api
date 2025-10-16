<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $q = $request->query('q');
        $user = $request->user();

        $students = Student::with('schoolClass') // eager load
            ->when($user->role === 'teacher', function ($builder) use ($user) {
                $builder->whereHas('schoolClass', function ($q) use ($user) {
                    $q->where('teacher', $user->name);
                });
            })
            ->when($q, function ($builder, $term) {
                $builder->where(function ($w) use ($term) {
                    $w->where('barcode', 'like', "%{$term}%")
                        ->orWhere('full_name', 'like', "%{$term}%")
                        ->orWhere('parent_contact', 'like', "%{$term}%")
                        ->orWhereHas('schoolClass', function ($sc) use ($term) {
                            $sc->where('grade_level', 'like', "%{$term}%")
                                ->orWhere('section', 'like', "%{$term}%");
                        });
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($students);
    }


    /**
     * Store a newly created student.
     */
    public function store(Request $request)
    {

        Configuration::instance(env('CLOUDINARY_URL'));

        $validated = $request->validate([
            'full_name'      => 'required|string|max:255',
            'parent_name'    => 'required|string|max:255',
            'parent_contact' => 'required|string|max:255',
            'class_id'       => 'required|exists:classes,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Find the last student and generate the next barcode
        $lastStudent = Student::orderBy('id', 'desc')->first();
        $nextNumber = $lastStudent
            ? ((int) substr($lastStudent->barcode, 4)) + 1
            : 1;

        $barcode = 'STU-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);



        $file = $request->file('photo');
        $photoUrl = null;

        // Upload photo
        if ($file) {
            $upload = new UploadApi();
            $result = $upload->upload($file->getRealPath(), [
                'folder' => 'student',
                'public_id' => $barcode,
            ]);
            $photoUrl = $result['secure_url'];
        }

        // Merge barcode with validated data
        $student = Student::create(array_merge($validated, [
            'barcode' => $barcode,
            'photo_url' => $photoUrl,
        ]));

        return response()->json([
            'message' => 'Student created successfully',
            'data'    => $student->load('schoolClass'),
        ], 201);
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student)
    {
        return response()->json($student->load('schoolClass'));
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, Student $student)
    {
        Configuration::instance(env('CLOUDINARY_URL'));

        $validated = $request->validate([
            'full_name'      => 'required|string|max:255',
            'parent_name'    => 'required|string|max:255',
            'parent_contact' => 'required|string|max:255',
            'class_id'       => 'required|exists:classes,id',
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $upload = new UploadApi();
            $result = $upload->upload($file->getRealPath(), [
                'folder' => 'student',
                'public_id' => $student->barcode,
                'overwrite' => true,
            ]);
            $validated['photo_url'] = $result['secure_url'];
        }

        unset($validated['photo']);

        $student->update($validated);

        return response()->json([
            'message' => 'Student updated successfully',
            'data'    => $student->load('schoolClass'),
        ]);
    }




    public function destroy(Student $student): Response
    {
        $student->update(["is_active" => 0]);
        return response()->noContent();
    }
}
