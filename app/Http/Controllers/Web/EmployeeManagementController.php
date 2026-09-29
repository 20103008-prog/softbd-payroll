<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\AttendancePolicy;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Attendance\PunchRecorder;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Roster\ShiftResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmployeeManagementController extends Controller
{
    /**
     * Display a listing of employees with search and pagination support.
     * Also retrieves unregistered biometric attendance codes from attendance logs.
     */
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $employees = Employee::query()
            ->with(['department', 'designation'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('designation', fn ($d) => $d->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $unregisteredCodes = DB::table('attendance_logs')
            ->distinct()
            ->whereNotIn('employee_code', Employee::query()->pluck('employee_code'))
            ->orderBy('employee_code')
            ->pluck('employee_code');

        return view('employees.index', compact('employees', 'unregisteredCodes', 'search'));
    }

    /**
     * Display the specified employee profile.
     * Loads attendance logs, late counts, leave balances, shift assignments, and absence reports.
     */
    public function show(Request $request, Employee $employee, LeaveBalanceService $balances, ShiftResolver $shifts): View
    {
        abort_unless($request->user()->canManageEmployeeProfile($employee), 403, 'You do not have permission to view or manage this employee profile.');

        $employee->load([
            'department',
            'designation',
            'user',
            'salaryStructure',
            'salaryComponents' => fn ($q) => $q->orderBy('type')->orderBy('name'),
            'shiftAssignments' => fn ($q) => $q->with('shift')->orderByDesc('effective_from')->limit(8),
        ]);

        $recentPunches = AttendanceLog::query()
            ->with('device')
            ->where('employee_code', $employee->employee_code)
            ->orderByDesc('punched_at')
            ->limit(12)
            ->get();

        $todayLastPunch = AttendanceLog::query()
            ->where('employee_code', $employee->employee_code)
            ->whereDate('punched_at', now()->toDateString())
            ->orderByDesc('punched_at')
            ->first();

        // Calculate current month attendance and late days
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $policy = AttendancePolicy::current();

        $days = DB::table('attendance_logs as al')
            ->where('al.employee_code', $employee->employee_code)
            ->whereBetween('al.punched_at', [$start->format('Y-m-d').' 00:00:00', $end->format('Y-m-d').' 23:59:59'])
            ->groupByRaw('DATE(al.punched_at)')
            ->selectRaw('DATE(al.punched_at) as d, MIN(al.punched_at) as check_in')
            ->get();

        $lateDays = 0;
        foreach ($days as $day) {
            $late = $shifts->lateInfo($employee, $day->d, $day->check_in, $policy);
            if ($late['late_minutes'] > 0) {
                $lateDays++;
            }
        }

        $leaves = $employee->leaveRequests()
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->limit(10)
            ->get();

        $approvedLeaveDays = (int) $employee->leaveRequests()
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('days');

        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->with('payrollRun')
            ->get()
            ->sortByDesc(fn ($p) => optional($p->payrollRun)->year * 100 + optional($p->payrollRun)->month)
            ->values();

        $monthParam = (string) $request->query('month', '');
        try {
            $monthStart = $monthParam !== ''
                ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
        }

        $currentShift = $shifts->shiftFor($employee, now());

        return view('employees.show', [
            'employee' => $employee,
            'recentPunches' => $recentPunches,
            'todayLastPunch' => $todayLastPunch,
            'presentDays' => $days->count(),
            'lateDays' => $lateDays,
            'leaves' => $leaves,
            'approvedLeaveDays' => $approvedLeaveDays,
            'payslips' => $payslips,
            'leaveBalances' => $balances->forEmployee($employee, now()->year),
            'absence' => $this->absenceReport($employee, $monthStart),
            'currentShift' => $currentShift,
        ]);
    }

    /**
     * Record a one-click manual check-in or check-out punch for an employee profile.
     */
    public function punchNow(Request $request, Employee $employee, PunchRecorder $punches): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        abort_unless($user->canManageEmployeeProfile($employee), 403, 'You do not have permission to record punches for this employee profile.');

        try {
            $log = $punches->record($employee);
        } catch (QueryException $e) {
            return back()->with('warning', 'A punch with the same time and status already exists for this employee.');
        }

        $label = $log->status === 'in' ? 'Check In' : 'Check Out';

        return back()->with('success', $label.' recorded at '.$log->punched_at->format('g:i A').'.');
    }

    /**
     * Calculate monthly absence report excluding weekends, holidays, and approved leaves.
     */
    private function absenceReport(Employee $employee, Carbon $monthStart): array
    {
        $start = $monthStart->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = now()->endOfDay();
        $rangeEnd = $end->lessThan($today) ? $end : $today;

        $punchDates = DB::table('attendance_logs')
            ->where('employee_code', $employee->employee_code)
            ->whereBetween('punched_at', [$start->format('Y-m-d').' 00:00:00', $end->format('Y-m-d').' 23:59:59'])
            ->selectRaw('DATE(punched_at) as d')
            ->distinct()
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        $holidays = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        $leaveDates = [];
        $approved = $employee->leaveRequests()
            ->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get(['start_date', 'end_date']);
        foreach ($approved as $lv) {
            for ($d = $lv->start_date->copy(); $d->lte($lv->end_date); $d->addDay()) {
                $leaveDates[$d->toDateString()] = true;
            }
        }

        $absentDates = [];
        $workingDays = 0;
        $presentDays = 0;
        $leaveDays = 0;

        for ($date = $start->copy(); $date->lte($rangeEnd); $date->addDay()) {
            $ds = $date->toDateString();

            if ($employee->isWeekend($date) || isset($holidays[$ds])) {
                continue;
            }

            $workingDays++;

            if (isset($punchDates[$ds])) {
                $presentDays++;
            } elseif (isset($leaveDates[$ds])) {
                $leaveDays++;
            } else {
                $absentDates[] = $date->copy();
            }
        }

        return [
            'month' => $start->format('Y-m'),
            'monthLabel' => $start->format('F Y'),
            'absentDates' => $absentDates,
            'workingDays' => $workingDays,
            'presentDays' => $presentDays,
            'leaveDays' => $leaveDays,
            'absentCount' => count($absentDates),
        ];
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        return view('employees.create', $this->formData());
    }

    /**
     * Store a newly created employee in storage and sync portal login details.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEmployee($request);
        $portal = $this->validatePortalLogin($request);

        $employee = Employee::query()->create($validated);
        $this->syncPortalLogin($employee, $portal);//auto user account  creating

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): View
    {
        abort_unless(request()->user()->canManageEmployeeProfile($employee), 403, 'You do not have permission to edit this employee profile.');

        $employee->load('user');

        return view('employees.edit', array_merge(['employee' => $employee], $this->formData()));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->canManageEmployeeProfile($employee), 403, 'You do not have permission to edit this employee profile.');

        $employee->load('user');
        $validated = $this->validateEmployee($request, $employee);
        $portal = $this->validatePortalLogin($request, $employee);

        $employee->update($validated);
        $this->syncPortalLogin($employee->fresh(), $portal);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $codeRule = Rule::unique('employees', 'employee_code');
        if ($employee) {
            $codeRule = $codeRule->ignore($employee->id);//ignore error if employee is updating
        }

        $joiningDateRules = ['nullable', 'date'];
        if (! $employee || ($request->filled('joining_date') && $request->input('joining_date') !== $employee->joining_date?->format('Y-m-d'))) {
            $joiningDateRules[] = 'after_or_equal:today';
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['required', 'string', 'max:255', $codeRule],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'joining_date' => $joiningDateRules,
            'status' => ['required', 'in:active,inactive'],
            'address' => ['nullable', 'string', 'max:500'],
            'weekend_days' => ['nullable', 'array'],
            'weekend_days.*' => ['integer', 'between:0,6'],
        ], [
            'phone.regex' => 'Please enter a valid phone number (digits, +, -, spaces, or parentheses, 7-20 characters).',
            'joining_date.after_or_equal' => 'The joining date cannot be a past date.',
        ]);

        // Always persist an explicit array (empty = works every day) so the
        // employee's own setting is used rather than the app default.
        $validated['weekend_days'] = array_values(array_unique(
            array_map('intval', $request->input('weekend_days', []))
        ));

        return $validated;
    }

    /**
     * Validate optional portal login fields from the employee form.
     *
     * @return array{enabled: bool, email: ?string, password: ?string, linked: ?User, staff_locked: bool}
     */
    private function validatePortalLogin(Request $request, ?Employee $employee = null): array
    {
        $linked = $employee?->user;
        $staffLocked = $linked !== null && ! $linked->isPortalOnly();
        $enabled = $request->boolean('enable_portal_login');

        if ($staffLocked) {
            // Staff accounts are managed under Users & Roles — ignore portal toggles.
            return [
                'enabled' => true,
                'email' => null,
                'password' => null,
                'linked' => $linked,
                'staff_locked' => true,
            ];
        }

        $creating = $enabled && $linked === null;
        $emailRule = Rule::unique('users', 'email');
        if ($linked) {
            $emailRule = $emailRule->ignore($linked->id);
        }

        $rules = [
            'portal_email' => [
                Rule::requiredIf($enabled),
                'nullable',
                'email',
                'max:255',
                $emailRule,
            ],
            'portal_password' => [
                Rule::requiredIf($creating),
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
        ];

        $validated = $request->validate($rules);

        return [
            'enabled' => $enabled,
            'email' => $validated['portal_email'] ?? null,
            'password' => $validated['portal_password'] ?? null,
            'linked' => $linked,
            'staff_locked' => false,
        ];
    }

    /**
     * Create, update, or remove a portal-only User linked to this employee.
     *
     * @param  array{enabled: bool, email: ?string, password: ?string, linked: ?User, staff_locked: bool}  $portal
     */
    private function syncPortalLogin(Employee $employee, array $portal): void
    {
        if ($portal['staff_locked']) {
            return;
        }

        $linked = $portal['linked'];//previously linked or not

        if (! $portal['enabled']) {
            if ($linked && $linked->isPortalOnly()) {
                $linked->delete();
            }

            return;
        }

        $email = (string) $portal['email'];

        if ($linked === null) {
            User::query()->create([
                'name' => $employee->name,
                'email' => $email,
                'password' => $portal['password'],
                'role' => User::ROLE_USER,
                'employee_id' => $employee->id,
            ]);
        } else {
            $linked->name = $employee->name;
            $linked->email = $email;
            if (! empty($portal['password'])) {
                $linked->password = $portal['password'];
            }
            $linked->save();
        }

        if (blank($employee->email)) {
            $employee->update(['email' => $email]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
        ];
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        abort_unless(request()->user()->canManageEmployeeProfile($employee), 403, 'You do not have permission to delete this employee profile.');

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    /**
     * Create placeholder employee rows for codes that appear in attendance logs
     * but have no matching employee record yet.
     */
    public function importMissing(): RedirectResponse
    {
        $existing = Employee::query()->pluck('employee_code')->all();

        $codes = DB::table('attendance_logs')
            ->distinct()
            ->whereNotIn('employee_code', $existing)
            ->pluck('employee_code')
            ->filter(fn ($code) => $code !== null && $code !== '')
            ->unique();

        $created = 0;
        foreach ($codes as $code) {
            Employee::query()->create([
                'name' => 'Employee '.$code,
                'employee_code' => $code,
            ]);
            $created++;
        }

        if ($created === 0) {
            return redirect()->route('employees.index')->with('warning', 'No unregistered codes found — every code in attendance logs already has an employee.');
        }

        return redirect()->route('employees.index')->with('success', "Imported {$created} employee(s) from attendance logs. Edit them to set real names.");
    }
}
