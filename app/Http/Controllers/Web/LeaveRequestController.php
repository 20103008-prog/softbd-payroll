<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Audit\AuditLogger;
use App\Services\Notify\HrNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /* ========================================================================= */
    /* 1. INDEX METHOD: LIST & FILTER LEAVE REQUESTS */
    /* ========================================================================= */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $status = $request->query('status');
        $employeeId = $request->query('employee_id');

        // Retrieve scoped employees based on user management permissions
        $employeesQuery = Employee::query()->orderBy('name');
        $user->scopeManagedEmployees($employeesQuery);

        // Query leave requests filtered by department scope, status, and employee ID
        $requests = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->when($user->isTeamScoped(), function ($q) use ($user) {
                $deptId = $user->managedDepartmentId();
                if ($deptId === null) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->whereHas('employee', fn ($e) => $e->where('department_id', $deptId));
                }
            })
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status', $status))
            ->when($employeeId !== null && $employeeId !== '', fn ($q) => $q->where('employee_id', $employeeId))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        $scopedEmployeeIds = (clone $employeesQuery)->pluck('id');

        return view('leave.index', [
            'requests' => $requests,
            'employees' => $employeesQuery->get(['id', 'name', 'employee_code']),
            'filters' => ['status' => $status, 'employee_id' => $employeeId],
            'summary' => [
                'pending' => LeaveRequest::where('status', LeaveRequest::STATUS_PENDING)
                    ->when($user->isTeamScoped(), fn ($q) => $q->whereIn('employee_id', $scopedEmployeeIds))
                    ->count(),
                'approved' => LeaveRequest::where('status', LeaveRequest::STATUS_APPROVED)
                    ->when($user->isTeamScoped(), fn ($q) => $q->whereIn('employee_id', $scopedEmployeeIds))
                    ->count(),
                'rejected' => LeaveRequest::where('status', LeaveRequest::STATUS_REJECTED)
                    ->when($user->isTeamScoped(), fn ($q) => $q->whereIn('employee_id', $scopedEmployeeIds))
                    ->count(),
            ],
            'teamScoped' => $user->isTeamScoped(),
            'managedDepartmentId' => $user->managedDepartmentId(),
        ]);
    }

    /* ========================================================================= */
    /* 2. CREATE METHOD: SHOW FORM FOR NEW LEAVE REQUEST */
    /* ========================================================================= */
    public function create(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $employeesQuery = Employee::query()->orderBy('name');
        $user->scopeManagedEmployees($employeesQuery);

        return view('leave.create', [
            'employees' => $employeesQuery->get(['id', 'name', 'employee_code']),
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'selectedEmployeeId' => $request->query('employee_id'),
        ]);
    }

    /* ========================================================================= */
    /* 3. STORE METHOD: SAVE NEW LEAVE REQUEST & VALIDATE */
    /* ========================================================================= */
    public function store(Request $request, LeaveBalanceService $balances, HrNotifier $notifier, AuditLogger $audit): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Step A: Validate request inputs (start_date, end_date, etc.)
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after:today'], // Future date validation constraint
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ], [
            'start_date.after' => 'Leave requests can only be made for future dates (after today).',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        $employee = Employee::findOrFail($validated['employee_id']);
        abort_unless($user->canAccessEmployee($employee), 403);

        $dummyLeave = new LeaveRequest(['employee_id' => $employee->id]);
        $dummyLeave->setRelation('employee', $employee);
        if ($validated['status'] === LeaveRequest::STATUS_APPROVED &&  !$user->canApproveLeaveRequest($dummyLeave)) {
            return back()
                ->withInput()
                ->withErrors(['status' => 'You do not have permission to approve a leave request for this employee/role. Please save it as pending.']);
        }

        $type = LeaveType::findOrFail($validated['leave_type_id']);

        // Step B: Calculate working days excluding weekends and public holidays
        $days = $this->workingDays($start, $end, $employee->weekendDays());

        if ($days < 1) {
            return back()
                ->withInput()
                ->withErrors(['start_date' => "The selected range has no working days for this employee — their weekend ({$employee->weekendLabel()}) and holidays are excluded."]);
        }

        // Step C: Check annual quota overflow
        $over = $this->quotaOverflow($balances, $employee, $type, $start->year, $days);

        if ($over !== null && $validated['status'] === LeaveRequest::STATUS_APPROVED) {
            return back()
                ->withInput()
                ->withErrors(['leave_type_id' => $this->overflowMessage($type, $over, $days).' Save it as pending instead, or use an unpaid leave type.']);
        }

        // Step D: Create leave request record
        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $validated['reason'] ?? null,
            'status' => $validated['status'],
            'reviewed_at' => $validated['status'] === LeaveRequest::STATUS_PENDING ? null : now(),
        ]);

        // Step E: Register audit log & notifications
        $audit->log('leave.created', "Leave {$leave->status} for {$employee->name} ({$days} day(s))", $leave, [
            'employee_id' => $employee->id,
            'status' => $leave->status,
            'days' => $days,
        ]);

        if ($leave->status === LeaveRequest::STATUS_PENDING) {
            $notifier->leaveSubmitted($leave);
        } elseif (in_array($leave->status, [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED], true)) {
            $notifier->leaveReviewed($leave);
        }

        if ($over !== null) {
            return redirect()->route('leave.index')
                ->with('warning', $this->overflowMessage($type, $over, $days).' Recorded as '.$validated['status'].'.');
        }

        return redirect()->route('leave.index')->with('success', 'Leave record saved.');
    }

    /* ========================================================================= */
    /* 4. APPROVE METHOD: APPROVE LEAVE REQUEST  */
    /* ========================================================================= */
    public function approve(LeaveRequest $leave, LeaveBalanceService $balances, HrNotifier $notifier, AuditLogger $audit): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $leave->loadMissing(['leaveType', 'employee']);

        abort_unless($user->canApproveLeaveRequest($leave), 403, 'You do not have permission to approve this leave request.');

        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('warning', 'This leave request has already been reviewed.');
        }

        $employee = $leave->employee;

        if ($employee && $leave->leaveType) {
            $over = $this->quotaOverflow($balances, $employee, $leave->leaveType, $leave->start_date->year, $leave->days, $leave->id);
            if ($over !== null) {
                return back()->with('error', $this->overflowMessage($leave->leaveType, $over, $leave->days).' Cannot approve.');
            }
        }

        $leave->update(['status' => LeaveRequest::STATUS_APPROVED, 'reviewed_at' => now()]);
        $notifier->leaveReviewed($leave->fresh(['leaveType', 'employee']));
        $audit->log('leave.approved', "Approved leave #{$leave->id} for {$leave->employee?->name}", $leave);

        return back()->with('success', 'Leave approved.');
    }

    /* ========================================================================= */
    /* 5. REJECT METHOD: REJECT LEAVE REQUEST */
    /* ========================================================================= */
    public function reject(LeaveRequest $leave, HrNotifier $notifier, AuditLogger $audit): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $leave->loadMissing('employee');

        abort_unless($user->canApproveLeaveRequest($leave), 403, 'You do not have permission to reject this leave request.');

        if ($leave->status !== LeaveRequest::STATUS_PENDING) {
            return back()->with('warning', 'This leave request has already been reviewed.');
        }

        $leave->update(['status' => LeaveRequest::STATUS_REJECTED, 'reviewed_at' => now()]);
        $notifier->leaveReviewed($leave->fresh(['leaveType', 'employee']));
        $audit->log('leave.rejected', "Rejected leave #{$leave->id} for {$leave->employee?->name}", $leave);

        return back()->with('success', 'Leave rejected.');
    }

    /* ========================================================================= */
    /* 6. DESTROY METHOD: DELETE LEAVE RECORD */
    /* ========================================================================= */
    public function destroy(LeaveRequest $leave, AuditLogger $audit): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $leave->loadMissing('employee');

        abort_unless($user->canDeleteLeaveRequest($leave), 403, 'You do not have permission to delete this leave request.');

        $summary = "Deleted leave #{$leave->id} for {$leave->employee?->name}";
        $leave->delete();
        $audit->log('leave.deleted', $summary);

        return back()->with('success', 'Leave record deleted.');
    }

    /* ========================================================================= */
    /* HELPER FUNCTIONS: WORKING DAYS & QUOTA CALCULATIONS */
    /* ========================================================================= */
    private function workingDays(Carbon $start, Carbon $end, array $weekendDays): int
    {
        $holidays = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        $count = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (in_array((int) $d->dayOfWeek, $weekendDays, true) || isset($holidays[$d->toDateString()])) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    private function quotaOverflow(LeaveBalanceService $balances, Employee $employee, LeaveType $type, int $year, int $days, ?int $excludeId = null): ?int
    {
        if ($type->annual_quota <= 0) {
            return null;
        }

        $used = $balances->usedApprovedDays($employee, $type, $year, $excludeId);
        $remaining = $type->annual_quota - $used;

        return $days > $remaining ? $remaining : null;
    }

    private function overflowMessage(LeaveType $type, int $remaining, int $days): string
    {
        return sprintf(
            'This exceeds the %s quota — %d day(s) remaining but %d requested.',
            $type->name,
            max(0, $remaining),
            $days
        );
    }
}
