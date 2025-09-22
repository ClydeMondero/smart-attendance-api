<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /dashboard/summary
    public function summary(Request $request)
    {
        $today = Carbon::today();

        $totalStudents = Student::count();
        $totalClasses = SchoolClass::count();

        $present = Attendance::whereDate('created_at', $today)
            ->where('status', 'present')
            ->count();

        $late = Attendance::whereDate('created_at', $today)
            ->where('status', 'late')
            ->count();

        // Absent = enrolled students - present - late
        $absent = $totalStudents - ($present + $late);

        return response()->json([
            'students' => $totalStudents,
            'classes' => $totalClasses,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
        ]);
    }

    // GET /dashboard/trend?days=7
    public function trend(Request $request)
    {
        $days = $request->query('days', 7);
        $startDate = Carbon::today()->subDays($days - 1);

        $records = Attendance::select(
            DB::raw('DATE(created_at) as day'),
            'status',
            DB::raw('COUNT(*) as total')
        )
            ->whereDate('created_at', '>=', $startDate)
            ->groupBy('day', 'status')
            ->orderBy('day')
            ->get();

        // Format into [{ day, present, absent, late }]
        $data = [];
        foreach (range(0, $days - 1) as $i) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            $data[$date] = [
                'day' => $date,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
            ];
        }

        foreach ($records as $row) {
            $data[$row->day][$row->status] = $row->total;
        }

        return array_values($data);
    }

    // GET /dashboard/classes?date=today
    public function classes(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        $classes = SchoolClass::withCount([
            'students as present' => function ($q) use ($date) {
                $q->whereHas(
                    'attendances',
                    fn($a) =>
                    $a->whereDate('created_at', $date)
                        ->where('status', 'present')
                );
            },
            'students as absent' => function ($q) use ($date) {
                $q->whereDoesntHave(
                    'attendances',
                    fn($a) =>
                    $a->whereDate('created_at', $date)
                );
            },
            'students as late' => function ($q) use ($date) {
                $q->whereHas(
                    'attendances',
                    fn($a) =>
                    $a->whereDate('created_at', $date)
                        ->where('status', 'late')
                );
            },
        ])->get();

        return $classes;
    }

    public function logs(Request $request)
    {
        $limit = $request->query('limit', 20);

        $logs = Attendance::with([
            'student:id,full_name,barcode,class_id',
            'student.schoolClass:id,grade_level,section',
        ])
            ->latest('created_at')
            ->take($limit)
            ->get()
            ->map(function ($att) {
                return [
                    'id'          => $att->id,
                    'studentName' => $att->student?->full_name,
                    'barcode'     => $att->student?->barcode,
                    'class'       => $att->student?->schoolClass
                        ? "{$att->student->schoolClass->grade_level}-{$att->student->schoolClass->section}"
                        : null,
                    'status'      => ucfirst($att->status ?? 'absent'),
                    'timeIn'      => $att->time_in ?: '-',
                    'timeOut'     => $att->time_out ?: '-',
                    'date'        => $att->log_date?->toDateString(),
                ];
            });

        return response()->json($logs);
    }
}
