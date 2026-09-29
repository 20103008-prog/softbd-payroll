<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Services\Reports\PeriodSummaryService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /* ========================================================================= */
    /* 1. CONSTRUCTOR: INJECT PERIOD SUMMARY REPORT SERVICE */
    /* ========================================================================= */
    public function __construct(private PeriodSummaryService $summaries)
    {
    }

    /* ========================================================================= */
    /* 2. INDEX METHOD: LOAD REAL-TIME DASHBOARD METRICS, STATS & CHARTS */
    /* ========================================================================= */
    public function index(): View|RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        // Redirect portal-only self-service users to their personal attendance view
        if ($user && $user->isPortalOnly() && $user->hasEmployeeProfile()) {
            return redirect()->route('me.attendance');
        }

        $today = today();
        $yesterday = today()->subDay();

        // Retrieve active managed employees list
        $employeeQuery = Employee::query()->where('status', 'active');
        if ($user) {
            $user->scopeManagedEmployees($employeeQuery);
        }
        $employees = (clone $employeeQuery)->with('department')->orderBy('name')->get();
        $employeesTotal = $employees->count();
        $employeeCodes = $employees->pluck('employee_code')->filter()->values();

        // Helper closure for scoping attendance punch logs by department permissions
        $scopedPunchBase = function () use ($user) {
            return AttendanceLog::query()
                ->leftJoin('employees as e', 'e.employee_code', '=', 'attendance_logs.employee_code')
                ->when($user?->isTeamScoped(), function ($q) use ($user) {
                    $deptId = $user->managedDepartmentId();
                    if ($deptId === null) {
                        $q->whereRaw('0 = 1');
                    } else {
                        $q->where('e.department_id', $deptId);
                    }
                });
        };

        // Calculate total attendance punch counts for today and yesterday
        $punchesToday = (clone $scopedPunchBase())
            ->whereDate('attendance_logs.punched_at', $today)
            ->count('attendance_logs.id');

        $punchesYesterday = (clone $scopedPunchBase())
            ->whereDate('attendance_logs.punched_at', $yesterday)
            ->count('attendance_logs.id');

        // Retrieve list of employee codes who have checked in today
        $presentCodes = (clone $scopedPunchBase())
            ->whereDate('attendance_logs.punched_at', $today)
            ->when($employeeCodes->isNotEmpty(), fn ($q) => $q->whereIn('attendance_logs.employee_code', $employeeCodes))
            ->distinct()
            ->pluck('attendance_logs.employee_code')
            ->filter()
            ->values();

        $presentToday = $presentCodes->count();

        // Fetch list of employees on approved leave today
        $onLeaveToday = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->when($user?->isTeamScoped(), function ($q) use ($user) {
                $deptId = $user->managedDepartmentId();
                if ($deptId === null) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->whereHas('employee', fn ($e) => $e->where('department_id', $deptId));
                }
            })
            ->orderBy('start_date')
            ->get();

        $onLeaveIds = $onLeaveToday->pluck('employee_id')->filter()->all();
        $holidayToday = Holiday::query()->whereDate('date', $today)->first();

        // Determine expected workforce count for today (excluding holidays, weekends, and leave)
        $expectedToday = $employees->filter(function (Employee $emp) use ($today, $onLeaveIds, $holidayToday) {
            if ($holidayToday) {
                return false;
            }
            if (in_array($emp->id, $onLeaveIds, true)) {
                return false;
            }
            if ($emp->isWeekend($today)) {
                return false;
            }

            return true;
        });

        // Compute missing / absent employees for today
        $missingToday = $expectedToday
            ->reject(fn (Employee $emp) => $presentCodes->contains($emp->employee_code))
            ->take(8)
            ->values();

        $expectedCount = $expectedToday->count();
        $missingCount = max(0, $expectedCount - $presentCodes->intersect(
            $expectedToday->pluck('employee_code')
        )->count());

        // Calculate today's attendance percentage rate
        $attendanceRate = $expectedCount > 0
            ? (int) round(($presentCodes->intersect($expectedToday->pluck('employee_code'))->count() / $expectedCount) * 100)
            : null;

        // Fetch count of late arrivals today from PeriodSummaryService
        $lateToday = 0;
        if ($user && $user->can('view-reports')) {
            try {
                $lateToday = (int) ($this->summaries->buildTab(
                    $user,
                    $today->toDateString(),
                    $today->toDateString(),
                    'late'
                )['total'] ?? 0);
            } catch (\Throwable) {
                $lateToday = 0;
            }
        }

        // Generate 7-day punch volume trend data for dashboard chart
        $trendStart = $today->copy()->subDays(6)->startOfDay();
        $trendRows = (clone $scopedPunchBase())
            ->where('attendance_logs.punched_at', '>=', $trendStart)
            ->selectRaw('DATE(attendance_logs.punched_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $weekTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $key = $d->toDateString();
            $weekTrend[] = [
                'date' => $key,
                'label' => $d->format('D'),
                'count' => (int) ($trendRows[$key] ?? 0),
                'is_today' => $i === 0,//if today ,highlight
            ];
        }
        $trendMax = max(1, ...array_column($weekTrend, 'count'));

        // Query biometric hardware device status counts
        $devicesTotal = Device::count();
        $devicesActive = Device::where('is_active', true)->count();
        $devicesInactive = max(0, $devicesTotal - $devicesActive);

        // Fetch pending leave approval requests
        $pendingLeaveQuery = LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)
            ->when($user?->isTeamScoped(), function ($q) use ($user) {
                $deptId = $user->managedDepartmentId();
                if ($deptId === null) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->whereHas('employee', fn ($e) => $e->where('department_id', $deptId));
                }
            });

        $pendingLeaveCount = (clone $pendingLeaveQuery)->count();
        $pendingLeaves = (clone $pendingLeaveQuery)
            ->with(['employee', 'leaveType'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Retrieve latest generated payroll run details
        $latestPayroll = PayrollRun::query()
            ->withSum('payslips', 'net_salary')
            ->withCount('payslips')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        // Calculate punch count delta compared to yesterday
        $punchDelta = $punchesToday - $punchesYesterday;
        $insights = $this->buildInsights($user, [
            'holiday' => $holidayToday,
            'attendance_rate' => $attendanceRate,
            'present' => $presentToday,
            'expected' => $expectedCount,
            'missing' => $missingCount,
            'late' => $lateToday,
            'on_leave' => $onLeaveToday->count(),
            'pending_leave' => $pendingLeaveCount,
            'punch_delta' => $punchDelta,
            'punches_today' => $punchesToday,
            'devices_inactive' => $devicesInactive,
            'latest_payroll' => $latestPayroll,
        ]);

        // Query 8 most recent attendance logs for dashboard activity feed
        $recent = AttendanceLog::query()
            ->leftJoin('employees as e', 'e.employee_code', '=', 'attendance_logs.employee_code')
            ->when($user?->isTeamScoped(), function ($q) use ($user) {
                $deptId = $user->managedDepartmentId();
                if ($deptId === null) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->where('e.department_id', $deptId);
                }
            })
            ->orderByDesc('attendance_logs.punched_at')
            ->limit(8)
            ->get([
                'attendance_logs.employee_code',
                'attendance_logs.punched_at',
                'attendance_logs.status',
                'e.name as employee_name',
            ]);
 // Query 
        foreach ($recent as $r) {
            $earlier = AttendanceLog::query()
                ->where('employee_code', $r->employee_code)
                ->whereDate('punched_at', Carbon::parse($r->punched_at)->toDateString())
                ->where('punched_at', '<', $r->punched_at)
                ->count();
            $r->display_status = $earlier % 2 === 0 ? 'in' : 'out';
        }

        // Assemble stats array for dashboard cards
        $stats = [
            'devices_total' => $devicesTotal,
            'devices_active' => $devicesActive,
            'devices_inactive' => $devicesInactive,
            'employees_total' => $employeesTotal,
            'punches_total' => AttendanceLog::count(),
            'punches_today' => $punchesToday,
            'punches_yesterday' => $punchesYesterday,
            'punch_delta' => $punchDelta,
            'present_today' => $presentToday,
            'expected_today' => $expectedCount,
            'missing_today' => $missingCount,
            'on_leave_today' => $onLeaveToday->count(),
            'late_today' => $lateToday,
            'attendance_rate' => $attendanceRate,
            'last_punch_at' => AttendanceLog::max('punched_at'),
        ];

        // Render main dashboard view (welcome.blade.php)
        return view('welcome', [
            'stats' => $stats,
            'recent' => $recent,
            'pendingLeaveCount' => $pendingLeaveCount,
            'pendingLeaves' => $pendingLeaves,
            'latestPayroll' => $latestPayroll,
            'insights' => $insights,
            'weekTrend' => $weekTrend,
            'trendMax' => $trendMax,
            'onLeaveToday' => $onLeaveToday->take(6),
            'missingToday' => $missingToday,
            'holidayToday' => $holidayToday,
        ]);
    }

    /* ========================================================================= */
    /* 3. BUILD INSIGHTS METHOD: GENERATE DYNAMIC SMART ALERT INSIGHTS */
    /* ========================================================================= */
    /**
     * @param  array<string, mixed>  $ctx
     * @return list<array{tone: string, icon: string, title: string, detail: string, href: ?string}>
     */
    private function buildInsights(?\App\Models\User $user, array $ctx): array
    {
        $canReports = (bool) $user?->can('view-reports');
        $canLeave = (bool) $user?->can('manage-leave');
        $canDevices = (bool) $user?->can('manage-devices');
        $canPayroll = (bool) $user?->can('manage-payroll');

        $items = [];

        // Insight 1: Holiday notification banner
        if ($ctx['holiday']) {
            $items[] = [
                'tone' => 'info',
                'icon' => 'bi-calendar-event',
                'title' => 'Holiday today — '.$ctx['holiday']->name,
                'detail' => 'Working-day attendance targets are paused for this date.',
                'href' => null,
            ];
        }

        // Insight 2: Pending leave approval requests warning
        if ($ctx['pending_leave'] > 0 && $canLeave) {
            $items[] = [
                'tone' => 'warn',
                'icon' => 'bi-hourglass-split',
                'title' => $ctx['pending_leave'].' leave request'.($ctx['pending_leave'] === 1 ? '' : 's').' waiting',
                'detail' => 'Approve or reject so balances and payroll stay accurate.',
                'href' => route('leave.index', ['status' => 'pending']),
            ];
        }

        // Insight 3: Attendance rate percentage status
        if ($ctx['attendance_rate'] !== null && ! $ctx['holiday']) {
            $rate = $ctx['attendance_rate'];
            $tone = $rate >= 90 ? 'ok' : ($rate >= 70 ? 'warn' : 'danger');
            $items[] = [
                'tone' => $tone,
                'icon' => 'bi-people',
                'title' => $rate.'% attendance so far today',
                'detail' => $ctx['present'].' present of '.$ctx['expected'].' expected'
                    .($ctx['missing'] > 0 ? ' · '.$ctx['missing'].' not punched yet' : ''),
                'href' => $canReports
                    ? route('reports.attendance', ['from' => today()->toDateString(), 'to' => today()->toDateString()])
                    : null,
            ];
        }

        // Insight 4: Late arrivals warning
        if ($ctx['late'] > 0 && $canReports) {
            $items[] = [
                'tone' => 'warn',
                'icon' => 'bi-alarm',
                'title' => $ctx['late'].' late arrival'.($ctx['late'] === 1 ? '' : 's').' today',
                'detail' => 'Review late summary and shift timings if this keeps rising.',
                'href' => route('reports.summary', ['tab' => 'late', 'from' => today()->toDateString(), 'to' => today()->toDateString()]),
            ];
        }

        // Insight 5: Staff on leave today notification
        if ($ctx['on_leave'] > 0) {
            $items[] = [
                'tone' => 'info',
                'icon' => 'bi-calendar-heart',
                'title' => $ctx['on_leave'].' on approved leave today',
                'detail' => 'They are excluded from today’s expected attendance count.',
                'href' => $canLeave ? route('leave.index', ['status' => 'approved']) : null,
            ];
        }

        // Insight 6: Attendance punch volume comparison vs yesterday
        if ($ctx['punch_delta'] !== 0 && $ctx['punches_today'] > 0) {
            $up = $ctx['punch_delta'] > 0;
            $items[] = [
                'tone' => $up ? 'ok' : 'warn',
                'icon' => $up ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow',
                'title' => abs($ctx['punch_delta']).' '.($up ? 'more' : 'fewer').' punches than yesterday',
                'detail' => 'Today '.$ctx['punches_today'].' · yesterday '.($ctx['punches_today'] - $ctx['punch_delta']),
                'href' => $canReports ? route('reports.attendance') : null,
            ];
        }

        // Insight 7: Unlocked payroll warning
        /** @var PayrollRun|null $payroll */
        $payroll = $ctx['latest_payroll'];
        if ($canPayroll && $payroll && ! $payroll->isLocked() && $payroll->status === 'generated') {
            $items[] = [
                'tone' => 'info',
                'icon' => 'bi-lock',
                'title' => 'Payroll '.$payroll->periodLabel().' is unlocked',
                'detail' => 'Lock it when payslips are final so it cannot be regenerated.',
                'href' => route('payroll.show', $payroll),
            ];
        }

        // Default Fallback Insight if no alerts are triggered
        if ($items === []) {
            $items[] = [
                'tone' => 'ok',
                'icon' => 'bi-check2-circle',
                'title' => 'All quiet — nothing urgent right now',
                'detail' => 'Use Ctrl+K to jump anywhere, or open today’s attendance report.',
                'href' => $canReports
                    ? route('reports.attendance', ['from' => today()->toDateString(), 'to' => today()->toDateString()])
                    : null,
            ];
        }

        return array_slice($items, 0, 6);
    }
}
