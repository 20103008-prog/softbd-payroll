<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Services\Roster\ShiftResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    /* ========================================================================= */
    /* 1. CONSTRUCTOR: INJECT SHIFT RESOLVER SERVICE */
    /* ========================================================================= */
    public function __construct(private ShiftResolver $shifts)
    {
    }

    /* ========================================================================= */
    /* 2. INDEX METHOD: GENERATE & FILTER DAILY ATTENDANCE REPORT SUMMARY */
    /* ========================================================================= */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $today = now()->toDateString();
        
        // Extract query filter parameters (date range from/to, selected employee)
        $from = $request->query('from', $today);
        $to = $request->query('to', $today);
        $employeeId = $request->query('employee_id');

        // Scope employee dropdown based on user access permissions
        $employeesQuery = Employee::query()->orderBy('name');
        $user->scopeManagedEmployees($employeesQuery);
        $employees = $employeesQuery->with('user')->get();

        // Build raw SQL query calculating daily check-in (MIN), check-out (MAX), and punch counts
        $query = DB::table('attendance_logs as al')
            ->leftJoin('employees as e', 'e.employee_code', '=', 'al.employee_code')
            ->selectRaw('DATE(al.punched_at) as work_date')
            ->selectRaw('al.employee_code as employee_code')
            ->selectRaw('e.id as employee_id') 
            ->selectRaw('COALESCE(e.name, al.employee_code) as employee_name')
            ->selectRaw("COALESCE(MIN(CASE WHEN al.status = 'in' THEN al.punched_at END), MIN(al.punched_at)) as check_in")
            ->selectRaw("CASE WHEN MAX(al.punched_at) > MIN(al.punched_at) THEN COALESCE(MAX(CASE WHEN al.status = 'out' THEN al.punched_at END), MAX(al.punched_at)) END as check_out")
            ->selectRaw('COUNT(*) as punch_count')
            ->when($from, fn ($q) => $q->where('al.punched_at', '>=', $from.' 00:00:00'))
            ->when($to, fn ($q) => $q->where('al.punched_at', '<=', $to.' 23:59:59'));

        // Apply team department scope restrictions if user is a manager
        if ($user->isTeamScoped()) {
            $deptId = $user->managedDepartmentId();
            if ($deptId === null) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('e.department_id', $deptId);
            }
        }

        // Apply single employee filter if selected in dropdown
        if ($employeeId) {
            $employee = Employee::query()->whereKey($employeeId)->first();
            if ($employee && $user->canAccessEmployee($employee)) {
                $query->where('al.employee_code', $employee->employee_code);
            } elseif ($employee) {
                abort(403);
            }
        }

        // Paginate daily attendance results (50 rows per page)
        $rows = $query
            ->groupByRaw('DATE(al.punched_at), al.employee_code, e.id, employee_name')
            ->orderByDesc('work_date')
            ->orderBy('employee_name')
            ->paginate(50)
            ->withQueryString();

        // Load attendance late policy and calculate roster shift assignments
        $policy = AttendancePolicy::current();
        $fromC = Carbon::parse($from)->startOfDay();
        $toC = Carbon::parse($to)->endOfDay();
        $employeeIds = $rows->getCollection()->pluck('employee_id')->filter()->unique()->values();
        $assignmentMap = $this->shifts->assignmentsForPeriod($employeeIds, $fromC, $toC);
        $employeeById = Employee::with('user')->whereIn('id', $employeeIds)->get()->keyBy('id');

        // Transform results to compute late arrival minutes and half-day statuses
        $lateCount = 0;
        $rows->getCollection()->transform(function ($r) use ($policy, &$lateCount, $assignmentMap, $employeeById) {
            $emp = $r->employee_id ? $employeeById->get($r->employee_id) : null;
            $shift = $emp ? $this->shifts->shiftFromMap($assignmentMap, (int) $emp->id, $r->work_date) : null;
            $late = $this->shifts->lateInfo($emp, $r->work_date, $r->check_in, $policy, $shift);
            $r->late_minutes = $late['late_minutes'];
            $r->is_half_day = $late['half_day'];
            $r->shift_name = $shift?->name;
            $r->target_user_role = $emp?->user?->role;
            if ($r->late_minutes > 0) {
                $lateCount++;
            }

            return $r;
        });

        // Compute report summary stat card counts (total punches, active employees, days, late count)
        $summaryBase = DB::table('attendance_logs as al')
            ->leftJoin('employees as e', 'e.employee_code', '=', 'al.employee_code')
            ->when($from, fn ($q) => $q->where('al.punched_at', '>=', $from.' 00:00:00'))
            ->when($to, fn ($q) => $q->where('al.punched_at', '<=', $to.' 23:59:59'));

        if ($user->isTeamScoped()) {
            $deptId = $user->managedDepartmentId();
            if ($deptId === null) {
                $summaryBase->whereRaw('0 = 1');
            } else {
                $summaryBase->where('e.department_id', $deptId);
            }
        }

        if ($employeeId && isset($employee) && $employee) {
            $summaryBase->where('al.employee_code', $employee->employee_code);
        }

        $summary = [
            'total_punches' => (clone $summaryBase)->count(),
            'employees' => (clone $summaryBase)->distinct()->count('al.employee_code'),
            'days' => (int) (clone $summaryBase)->selectRaw('COUNT(DISTINCT DATE(al.punched_at)) as c')->value('c'),
            'late' => $lateCount,
        ];

        // Render reports/attendance view
        return view('reports.attendance', [
            'employees' => $employees,
            'rows' => $rows,
            'summary' => $summary,
            'policy' => $policy,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'employee_id' => $employeeId,
            ],
        ]);
    }

    /* ========================================================================= */
    /* 3. EXPORT CSV METHOD: STREAM ATTENDANCE REPORT DATA AS A CSV FILE */
    /* ========================================================================= */
    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $today = now()->toDateString();
        $from = $request->query('from', $today);
        $to = $request->query('to', $today);
        $employeeId = $request->query('employee_id');

        // Build database query for export data
        $query = DB::table('attendance_logs as al')
            ->leftJoin('employees as e', 'e.employee_code', '=', 'al.employee_code')
            ->selectRaw('DATE(al.punched_at) as work_date')
            ->selectRaw('al.employee_code as employee_code')
            ->selectRaw('e.id as employee_id')
            ->selectRaw('COALESCE(e.name, al.employee_code) as employee_name')
            ->selectRaw("COALESCE(MIN(CASE WHEN al.status = 'in' THEN al.punched_at END), MIN(al.punched_at)) as check_in")
            ->selectRaw("CASE WHEN MAX(al.punched_at) > MIN(al.punched_at) THEN COALESCE(MAX(CASE WHEN al.status = 'out' THEN al.punched_at END), MAX(al.punched_at)) END as check_out")
            ->selectRaw('COUNT(*) as punch_count')
            ->when($from, fn ($q) => $q->where('al.punched_at', '>=', $from.' 00:00:00'))
            ->when($to, fn ($q) => $q->where('al.punched_at', '<=', $to.' 23:59:59'));

        // Enforce team department scope filter
        if ($user->isTeamScoped()) {
            $deptId = $user->managedDepartmentId();
            if ($deptId === null) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('e.department_id', $deptId);
            }
        }

        // Enforce employee filter
        if ($employeeId) {
            $employee = Employee::query()->whereKey($employeeId)->first();
            if ($employee && $user->canAccessEmployee($employee)) {
                $query->where('al.employee_code', $employee->employee_code);
            } elseif ($employee) {
                abort(403);
            }
        }

        // Group and order records for export
        $query->groupByRaw('DATE(al.punched_at), al.employee_code, e.id, employee_name')
            ->orderByDesc('work_date')
            ->orderBy('employee_name');

        $filename = 'attendance-report-'.$from.'-to-'.$to.'.csv';
        $policy = AttendancePolicy::current();
        $fromC = Carbon::parse($from)->startOfDay();
        $toC = Carbon::parse($to)->endOfDay();

        // Stream output response and write CSV headers and chunked data rows
        return response()->streamDownload(function () use ($query, $policy, $fromC, $toC) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

            // Write CSV Header Column Labels
            fputcsv($out, [
                'Date',
                'Employee Code',
                'Employee Name',
                'Shift',
                'Check In',
                'Check Out',
                'Worked Hours',
                'Punches',
                'Status',
                'Late (min)',
            ]);

            $asText = fn (string $value) => $value === '' ? '' : "\t".$value;

            // Chunk database results (2000 rows per batch) for memory efficiency
            $query->chunk(2000, function ($chunk) use ($out, $asText, $policy, $fromC, $toC) {
                $employeeIds = $chunk->pluck('employee_id')->filter()->unique()->values();
                $assignmentMap = $this->shifts->assignmentsForPeriod($employeeIds, $fromC, $toC);
                $employeeById = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

                foreach ($chunk as $r) {
                    $date = $r->work_date ? Carbon::parse($r->work_date)->format('Y-m-d') : '';
                    $checkIn = $r->check_in ? Carbon::parse($r->check_in) : null;
                    $checkOut = $r->check_out ? Carbon::parse($r->check_out) : null;

                    // Calculate total worked hours (difference between Check-In and Check-Out)
                    $worked = '';
                    if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
                        $minutes = $checkIn->diffInMinutes($checkOut);
                        $worked = sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
                    }

                    $emp = $r->employee_id ? $employeeById->get($r->employee_id) : null;
                    $shift = $emp ? $this->shifts->shiftFromMap($assignmentMap, (int) $emp->id, $r->work_date) : null;
                    $late = $this->shifts->lateInfo($emp, $r->work_date, $r->check_in, $policy, $shift);
                    $status = $late['half_day'] ? 'Half day' : ($late['late_minutes'] > 0 ? 'Late' : 'On time');

                    // Write formatted CSV row
                    fputcsv($out, [
                        $asText($date),
                        $r->employee_code,
                        $r->employee_name,
                        $shift?->name ?? '',
                        $asText($checkIn ? $checkIn->format('Y-m-d H:i:s') : ''),
                        $asText($checkOut ? $checkOut->format('Y-m-d H:i:s') : ''),
                        $worked,
                        $r->punch_count,
                        $status,
                        $late['late_minutes'] > 0 ? $late['late_minutes'] : '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
