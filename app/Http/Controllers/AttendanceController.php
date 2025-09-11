<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * List records with optional filters.
     * Filters: ?type=class|entry, ?q=search, ?date_from=YYYY-MM-DD, ?date_to=YYYY-MM-DD
     */
    public function index(Request $request)
    {
        $query = Attendance::query();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('grade_level', 'like', "%{$q}%")
                    ->orWhere('section', 'like', "%{$q}%")
                    ->orWhere('teacher', 'like', "%{$q}%")
                    ->orWhere('student_name', 'like', "%{$q}%")
                    ->orWhere('student_id', 'like', "%{$q}%");
            });
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('log_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('log_date', '<=', $to);
        }

        $attendances = $query->latest('id')->paginate(15)->withQueryString();

        // If you're returning views, swap this for: return view('attendances.index', compact('attendances'));
        return response()->json($attendances);
    }

    /** Show create form (for Blade apps). */
    public function create()
    {
        // return view('attendances.create');
        return response()->json(['message' => 'Render create form here']);
    }

    /** Store a new attendance row (class or entry). */
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $attendance = Attendance::create($data);

        // return redirect()->route('attendances.show', $attendance)->with('status', 'Created!');
        return response()->json($attendance, 201);
    }

    /** Display one row. */
    public function show(Attendance $attendance)
    {
        // return view('attendances.show', compact('attendance'));
        return response()->json($attendance);
    }

    /** Show edit form (for Blade apps). */
    public function edit(Attendance $attendance)
    {
        // return view('attendances.edit', compact('attendance'));
        return response()->json(['message' => 'Render edit form here', 'data' => $attendance]);
    }

    /** Update a row. */
    public function update(Request $request, Attendance $attendance)
    {
        $data = $this->validatedData($request, updating: true);

        $attendance->update($data);

        // return redirect()->route('attendances.show', $attendance)->with('status', 'Updated!');
        return response()->json($attendance);
    }

    /** Soft-delete a row. */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        // return redirect()->route('attendances.index')->with('status', 'Deleted!');
        return response()->json(['deleted' => true]);
    }

    /**
     * Centralized validation for both "class" and "entry" types.
     * - For type=class: require teacher/school_year/status (others optional)
     * - For type=entry: require log_date/student_name/student_id/time_in (time_out optional)
     */
    protected function validatedData(Request $request, bool $updating = false): array
    {
        $baseRules = [
            'type'         => ['required', Rule::in(['class', 'entry'])],

            // shared
            'grade_level'  => ['nullable', 'string', 'max:100'],
            'section'      => ['nullable', 'string', 'max:100'],

            // class fields
            'teacher'      => ['nullable', 'string', 'max:255'],
            'school_year'  => ['nullable', 'string', 'max:50'],
            'status'       => ['nullable', Rule::in(['active', 'inactive'])],

            // entry fields
            'log_date'     => ['nullable', 'date'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'student_id'   => ['nullable', 'string', 'max:100'],
            'time_in'      => ['nullable', 'date_format:H:i'],
            'time_out'     => ['nullable', 'date_format:H:i', 'after_or_equal:time_in'],
        ];

        // Add conditional requirements based on "type"
        $conditionalRules = [
            'teacher'      => ['required_if:type,class'],
            'school_year'  => ['required_if:type,class'],
            'status'       => ['required_if:type,class'],

            'log_date'     => ['required_if:type,entry', 'date'],
            'student_name' => ['required_if:type,entry'],
            'student_id'   => ['required_if:type,entry'],
            'time_in'      => ['required_if:type,entry', 'date_format:H:i'],
            // time_out remains optional
        ];

        $rules = array_merge_recursive($baseRules, $conditionalRules);

        $validated = $request->validate($rules);

        // Optional: normalize strings (trim)
        foreach (['grade_level', 'section', 'teacher', 'school_year', 'student_name', 'student_id'] as $k) {
            if (isset($validated[$k]) && is_string($validated[$k])) {
                $validated[$k] = trim($validated[$k]);
            }
        }

        return $validated;
    }
}
