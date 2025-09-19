<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use DateTimeInterface;

class AttendanceController extends Controller
{
    /** ---------------- Helpers (robust date/time handling) ---------------- */

    private function toDateString($d, ?string $fallback = null): string
    {
        if ($d instanceof DateTimeInterface) return Carbon::instance($d)->toDateString();
        $s = trim((string)$d);
        // If looks like YYYY-MM-DD or YYYY-MM-DD HH:MM[:SS], take the date part
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) return substr($s, 0, 10);
        return $fallback ?? now()->toDateString();
    }

    /**
     * Accepts:
     * - $time as "HH:MM[:SS]" or "YYYY-MM-DD HH:MM[:SS]" or DateTime
     * - $fallbackDate as "YYYY-MM-DD" (string or DateTime)
     * Returns a Carbon timestamp.
     */
    private function combineDateAndTime($fallbackDate, $time): Carbon
    {
        if ($time instanceof DateTimeInterface) {
            return Carbon::instance($time);
        }
        $dateStr = $this->toDateString($fallbackDate);
        $s = trim((string)$time);

        if ($s === '') return Carbon::parse("$dateStr 00:00:00");

        // full datetime?
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(:\d{2})?$/', $s)) {
            return Carbon::parse($s);
        }
        // time only?
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s)) {
            return Carbon::parse("$dateStr $s");
        }
        // date only?
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return Carbon::parse("$s 00:00:00");
        }
        // last resort: let Carbon guess
        return Carbon::parse($s);
    }

    /**
     * Compute present/late using either:
     *  - provided expected time override ($expectedOverride), or
     *  - the class's expected_time_in value.
     *
     * $logDate can be "YYYY-MM-DD" or a DateTime.
     * $timeIn may be "HH:MM:SS" (time-only) or a datetime string.
     */
    private function computeStatusForTimeIn(?SchoolClass $class, $logDate, $timeIn, int $graceMinutes = 5, ?string $expectedOverride = null): string
    {
        // choose which expected time value to use
        $expectedTimeValue = $expectedOverride ?? ($class->expected_time_in ?? null);

        if (empty($expectedTimeValue) || empty($timeIn)) {
            Log::warning('Attendance status fallback to present — missing data', [
                'class_id' => $class?->id,
                'logDate' => $logDate,
                'timeIn' => $timeIn,
                'expected_time_in' => $expectedTimeValue,
            ]);
            return 'present';
        }

        // make sure we have only date part for combining
        $datePart = $this->toDateString($logDate, now()->toDateString());

        try {
            $expected = $this->combineDateAndTime($datePart, $expectedTimeValue);
            $actual   = $this->combineDateAndTime($datePart, $timeIn);
        } catch (\Throwable $ex) {
            Log::error('Failed to parse expected/actual times for attendance', [
                'class_id' => $class?->id,
                'logDate' => $datePart,
                'expected_time_in' => $expectedTimeValue,
                'timeIn' => $timeIn,
                'exception' => $ex->getMessage(),
            ]);
            // safe fallback
            return 'present';
        }

        $expectedWithGrace = (clone $expected)->addMinutes($graceMinutes);

        Log::info('Attendance status computation', [
            'class_id' => $class?->id,
            'logDate' => $datePart,
            'expected_time_in_used' => $expectedTimeValue,
            'timeIn' => $timeIn,
            'expected' => $expected->toDateTimeString(),
            'expected_with_grace' => $expectedWithGrace->toDateTimeString(),
            'actual' => $actual->toDateTimeString(),
            'isLate' => $actual->greaterThan($expectedWithGrace),
        ]);

        return $actual->greaterThan($expectedWithGrace) ? 'late' : 'present';
    }

    /**
     * GET /api/attendances
     * Filters:
     *  - type=class|entry
     *  - class_id=ID (for class logs)
     *  - date=YYYY-MM-DD  (exact)
     *  - date_from=YYYY-MM-DD, date_to=YYYY-MM-DD
     *  - q= search (student name or barcode or status)
     */
    public function index(Request $request)
    {
        $q = Attendance::query()
            ->when($request->type, fn($x, $t) => $x->where('type', $t))
            ->when($request->class_id, fn($x, $cid) => $x->where('class_id', $cid))
            ->when($request->date, fn($x, $d) => $x->whereDate('log_date', $d))
            ->when($request->date_from, fn($x, $d) => $x->whereDate('log_date', '>=', $d))
            ->when($request->date_to, fn($x, $d) => $x->whereDate('log_date', '<=', $d))
            ->when($request->q, function ($x, $s) {
                $x->where(function ($w) use ($s) {
                    $w->whereHas('student', fn($st) => $st
                        ->where('full_name', 'like', "%$s%")
                        ->orWhere('barcode', 'like', "%$s%"))
                        ->orWhere('status', 'like', "%$s%");
                });
            })
            ->with(['student:id,full_name,barcode', 'schoolClass:id,grade_level,section']);

        // Compact table shape for ClassAttendanceLog page
        if ($request->type === 'class' && $request->filled(['class_id', 'date'])) {
            $rows = $q->orderByRaw('COALESCE(time_in, "99:99:99") asc')
                ->get()
                ->map(function (Attendance $a) use ($request) {
                    return [
                        'id'          => (string) $a->id,
                        'studentName' => $a->student?->full_name ?? '—',
                        'status'      => ucfirst($a->status ?? 'absent'),
                        'timeIn'      => $a->time_in ?: '-',
                        'timeOut'     => $a->time_out ?: '-',
                        'date'        => $this->toDateString($request->date),
                    ];
                });
            return ['data' => $rows];
        }

        // Generic paginated result
        return $q->latest('id')->paginate((int)$request->input('per_page', 15))->withQueryString();
    }

    /**
     * POST /api/attendances
     * Behaviors (resourceful via payload):
     *  A) Start Attendance (pre-seed all students of class/date as 'absent'):
     *     { action:"start", class_id, date }
     *
     *  B) Scan (toggle in/out via barcode):
     *     { action:"scan", class_id, date?, barcode, expected_time_in? }
     *
     *  C) Manual create (single row):
     *     { type, class_id?, student_id?, log_date, status?, time_in?, time_out?, note? }
     */
    public function store(Request $request)
    {
        $action = $request->input('action');

        // A) START
        if ($action === 'start') {
            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'date'     => ['required', 'date'],
            ]);

            DB::transaction(function () use ($data) {
                $class = SchoolClass::findOrFail($data['class_id']);
                $studentIds = $class->students()->pluck('id');
                foreach ($studentIds as $sid) {
                    Attendance::firstOrCreate(
                        [
                            'type'       => 'class',
                            'class_id'   => $class->id,
                            'student_id' => $sid,
                            'log_date'   => $data['date'],
                        ],
                        ['status' => 'absent']
                    );
                }
            });

            return response()->json(['ok' => true, 'action' => 'start'], 201);
        }

        // B) SCAN
        if ($action === 'scan') {
            $data = $request->validate([
                'class_id'         => ['nullable', 'integer', 'exists:classes,id'], // make it nullable
                'barcode'          => ['required', 'string'],
                'date'             => ['nullable', 'date'],
                'expected_time_in' => ['nullable', 'string'], // optional override (class only)
                'type'             => ['nullable', Rule::in(['class', 'entry'])], // allow explicit type
            ]);

            $type = $data['type'] ?? (!empty($data['class_id']) ? 'class' : 'entry');
            $providedExpected = $data['expected_time_in'] ?? null;

            $student = Student::where('barcode', $data['barcode'])->first();
            if (!$student) {
                return response()->json(['ok' => false, 'message' => 'Student not found'], 404);
            }

            $date = $data['date'] ?? now()->toDateString();
            $MIN_GAP_SECONDS = 10;
            $result = null;

            DB::transaction(function () use (&$result, $data, $student, $date, $MIN_GAP_SECONDS, $providedExpected, $type) {

                if ($type === 'class') {
                    // -------------------------
                    // CLASS ATTENDANCE LOGIC
                    // -------------------------
                    $att = Attendance::where([
                        'type'       => 'class',
                        'class_id'   => $data['class_id'],
                        'student_id' => $student->id,
                        'log_date'   => $date,
                    ])->lockForUpdate()->first();

                    $class = SchoolClass::findOrFail($data['class_id']);
                    $nowTime = now()->format('H:i:s');

                    if (!$att) {
                        $status = $this->computeStatusForTimeIn($class, $date, $nowTime, 5, $providedExpected);

                        $att = Attendance::create([
                            'type'       => 'class',
                            'class_id'   => $data['class_id'],
                            'student_id' => $student->id,
                            'log_date'   => $date,
                            'status'     => $status,
                            'time_in'    => $nowTime,
                        ]);

                        $result = ['ok' => true, 'action' => 'time_in', 'student' => $student->full_name, 'status' => $status];
                        return;
                    }

                    if (!$att->time_in) {
                        $att->time_in = $nowTime;
                        $att->status  = $this->computeStatusForTimeIn($class, $att->log_date ?? $date, $nowTime, 5, $providedExpected);
                        $att->save();

                        $result = ['ok' => true, 'action' => 'time_in', 'student' => $student->full_name, 'status' => $att->status];
                        return;
                    }

                    if (!$att->time_out) {
                        $inAt = $this->combineDateAndTime($att->log_date ?? $date, $att->time_in);
                        $now  = now();

                        if ($inAt->diffInSeconds($now) < $MIN_GAP_SECONDS) {
                            $result = [
                                'ok'      => true,
                                'action'  => 'noop_cooldown',
                                'student' => $student->full_name,
                                'message' => "Please wait at least {$MIN_GAP_SECONDS}s before timing out.",
                            ];
                            return;
                        }

                        $att->time_out = $now->format('H:i:s');
                        $att->save();

                        $result = ['ok' => true, 'action' => 'time_out', 'student' => $student->full_name];
                        return;
                    }

                    $result = ['ok' => true, 'action' => 'noop_done', 'student' => $student->full_name];
                }

                if ($type === 'entry') {
                    // -------------------------
                    // ENTRY ATTENDANCE LOGIC
                    // -------------------------
                    $att = Attendance::where([
                        'type'       => 'entry',
                        'student_id' => $student->id,
                        'log_date'   => $date,
                    ])->lockForUpdate()->first();

                    $nowTime = now()->format('H:i:s');

                    if (!$att) {
                        $att = Attendance::create([
                            'type'       => 'entry',
                            'student_id' => $student->id,
                            'log_date'   => $date,
                            'status'     => 'in',
                            'time_in'    => $nowTime,
                        ]);

                        $result = ['ok' => true, 'action' => 'time_in', 'student' => $student->full_name, 'status' => 'in'];
                        return;
                    }

                    if (!$att->time_out) {
                        $inAt = $this->combineDateAndTime($att->log_date ?? $date, $att->time_in);
                        $now  = now();

                        if ($inAt->diffInSeconds($now) < $MIN_GAP_SECONDS) {
                            $result = [
                                'ok'      => true,
                                'action'  => 'noop_cooldown',
                                'student' => $student->full_name,
                                'message' => "Please wait at least {$MIN_GAP_SECONDS}s before timing out.",
                            ];
                            return;
                        }

                        $att->time_out = $now->format('H:i:s');
                        $att->status   = 'out';
                        $att->save();

                        $result = ['ok' => true, 'action' => 'time_out', 'student' => $student->full_name];
                        return;
                    }

                    $result = ['ok' => true, 'action' => 'noop_done', 'student' => $student->full_name];
                }
            });

            return $result;
        }


        // C) MANUAL CREATE
        $data = $request->validate([
            'type'       => ['required', Rule::in(['class', 'entry'])],
            'class_id'   => ['nullable', 'integer', 'exists:classes,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'log_date'   => ['required', 'date'],
            'status'     => ['nullable', Rule::in(['present', 'late', 'absent', 'excused', 'in', 'out'])],
            'time_in'    => ['nullable', 'date_format:H:i:s'],
            'time_out'   => ['nullable', 'date_format:H:i:s', 'after_or_equal:time_in'],
            'note'       => ['nullable', 'string', 'max:255'],
        ]);

        // Auto-set status if missing but we have time_in
        if (($data['type'] ?? null) === 'class' && !empty($data['class_id']) && !empty($data['time_in']) && empty($data['status'])) {
            $class = SchoolClass::find($data['class_id']);
            $data['status'] = $this->computeStatusForTimeIn($class, $data['log_date'], $data['time_in'], 5);
        }

        $attendance = Attendance::create($data);
        return response()->json($attendance, 201);
    }

    /** GET /api/attendances/{attendance} */
    public function show(Attendance $attendance)
    {
        return $attendance->load(['student:id,full_name,barcode', 'schoolClass:id,grade_level,section']);
    }

    /** PUT/PATCH /api/attendances/{attendance} */
    /** PUT/PATCH /api/attendances/{attendance} */
    public function update(Request $request, Attendance $attendance)
    {
        // Validate incoming fields (seconds included to match scan flow)
        $data = $request->validate([
            'class_id' => ['sometimes', 'nullable', 'integer', 'exists:classes,id'],
            'student_id' => ['sometimes', 'nullable', 'integer', 'exists:students,id'],
            'log_date' => ['sometimes', 'date'],
            'status'   => ['sometimes', 'nullable', Rule::in(['present', 'late', 'absent', 'excused', 'in', 'out'])],
            'time_in'  => ['sometimes', 'nullable', 'date_format:H:i:s'],
            'time_out' => ['sometimes', 'nullable', 'date_format:H:i:s', 'after_or_equal:time_in'],
            'note'     => ['sometimes', 'nullable', 'string', 'max:255'],

            'expected_time_in' => ['nullable', 'string'], // optional override
        ]);

        // Apply changes first (but not saved yet)
        $att = clone $attendance;
        foreach ($data as $k => $v) {
            $att->{$k} = $v;
        }

        // Check if client explicitly sent a status
        $clientSentStatus = array_key_exists('status', $data);

        $isClassType = ($att->type ?? $attendance->type) === 'class';
        $classId = $att->class_id ?? $attendance->class_id;
        $timeIn  = $att->time_in ?? $attendance->time_in;
        $logDate = $att->log_date ?? $attendance->log_date;

        // Grab expected_time_in override if present
        $expectedOverride = $data['expected_time_in'] ?? null;

        if ($isClassType && $classId && $timeIn && !$clientSentStatus) {
            $class = SchoolClass::find($classId);

            // Pass the override to computeStatusForTimeIn
            $att->status = $this->computeStatusForTimeIn(
                $class,
                $logDate,
                $timeIn,
                5,
                $expectedOverride
            );
        }

        // Persist
        $attendance->update($att->getAttributes());

        return $attendance->fresh()->load('student:id,full_name,barcode');
    }


    /** DELETE /api/attendances/{attendance} */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return response()->json(['deleted' => true]);
    }

    /** OPTIONAL: CSV export – /api/attendances/export?class_id=&date= */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date'     => ['required', 'date'],
        ]);

        $rows = Attendance::where('type', 'class')
            ->where('class_id', $request->class_id)
            ->whereDate('log_date', $request->date)
            ->with('student:id,full_name,barcode')
            ->orderBy('time_in')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_' . $request->class_id . '_' . $request->date . '.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student Name', 'Barcode', 'Status', 'Time In', 'Time Out', 'Date']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->student?->full_name,
                    $r->student?->barcode,
                    ucfirst($r->status ?? 'absent'),
                    $r->time_in,
                    $r->time_out,
                    $this->toDateString($r->log_date),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }


    // GET /api/attendances/stats?date=2025-09-19&type=class
    public function stats(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $yesterday = \Carbon\Carbon::parse($date)->subDay()->toDateString();
        $type = $request->input('type', 'class'); // default to class

        if ($type === 'class') {
            $todayCounts = Attendance::where('type', 'class')
                ->whereDate('log_date', $date)
                ->selectRaw("SUM(status IN ('present','late')) as present_count")
                ->selectRaw("SUM(status = 'absent') as absent_count")
                ->first();

            $yesterdayCounts = Attendance::where('type', 'class')
                ->whereDate('log_date', $yesterday)
                ->selectRaw("SUM(status IN ('present','late')) as present_count")
                ->selectRaw("SUM(status = 'absent') as absent_count")
                ->first();

            return response()->json([
                'today' => [
                    'present' => (int) $todayCounts->present_count,
                    'absent'  => (int) $todayCounts->absent_count,
                ],
                'yesterday' => [
                    'present' => (int) $yesterdayCounts->present_count,
                    'absent'  => (int) $yesterdayCounts->absent_count,
                ],
            ]);
        }

        if ($type === 'entry') {
            $todayCounts = Attendance::where('type', 'entry')
                ->whereDate('log_date', $date)
                ->selectRaw("SUM(status = 'in') as in_count")
                ->selectRaw("SUM(status = 'out') as out_count")
                ->first();

            $yesterdayCounts = Attendance::where('type', 'entry')
                ->whereDate('log_date', $yesterday)
                ->selectRaw("SUM(status = 'in') as in_count")
                ->selectRaw("SUM(status = 'out') as out_count")
                ->first();

            return response()->json([
                'today' => [
                    'in'  => (int) $todayCounts->in_count,
                    'out' => (int) $todayCounts->out_count,
                ],
                'yesterday' => [
                    'in'  => (int) $yesterdayCounts->in_count,
                    'out' => (int) $yesterdayCounts->out_count,
                ],
            ]);
        }

        return response()->json(['error' => 'Invalid type'], 400);
    }
}
