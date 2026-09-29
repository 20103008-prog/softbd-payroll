@extends('layouts.app')

@section('title', $employee->name)

@php
    $canManageProfile = auth()->user()?->canManageEmployeeProfile($employee) ?? true;
    $basic = (float) ($employee->salaryStructure->monthly_salary ?? 0);
    $earnTotal = $employee->salaryComponents->where('type', 'earning')->sum(fn ($c) => $c->resolve($basic));
    $dedTotal = $employee->salaryComponents->where('type', 'deduction')->sum(fn ($c) => $c->resolve($basic));
    $grossPackage = $basic + $earnTotal;
    $netPackage = $grossPackage - $dedTotal;
@endphp

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. PAGE NAVIGATION & PROFILE HEADER ACTION BUTTONS --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="page-actions">
            {{-- Back Navigation Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('employees.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
        <div class="page-actions">
            {{-- Salary Components Management Button --}}
            @can('manage-payroll') 
                <a class="btn btn-outline-secondary" href="{{ route('payroll.components', $employee) }}"><i class="bi bi-sliders me-1"></i><span class="d-none d-sm-inline">Salary components</span><span class="d-sm-none">Salary</span></a>
            @endcan
            {{-- Edit Profile Action Button --}}
            @if($canManageProfile)
                <a class="btn btn-primary" href="{{ route('employees.edit', $employee) }}"><i class="bi bi-pencil me-1"></i>Edit</a>
            @else
                <button class="btn btn-primary opacity-50" disabled title="Editing restricted"><i class="bi bi-pencil me-1"></i>Edit</button>
            @endif
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. EMPLOYEE PROFILE SUMMARY & PUNCH BUTTON CARD --}}
    {{-- ========================================================= --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3">
                {{-- Employee Avatar & Basic Info --}}
                <span class="avatar" style="width:64px;height:64px;font-size:1.6rem;">{{ strtoupper(mb_substr($employee->name, 0, 1)) }}</span>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h4 mb-0">{{ $employee->name }}</h1>
                        @if(($employee->status ?? 'active') === 'active')
                            <span class="badge text-bg-success-subtle text-success border border-success-subtle">Active</span>
                        @else
                            <span class="badge text-bg-secondary-subtle text-secondary border">Inactive</span>
                        @endif
                    </div>
                    <div class="text-muted">
                        {{ optional($employee->designation)->name ?? 'No designation' }}
                        @if($employee->department) &middot; {{ $employee->department->name }} @endif
                    </div>
                </div>
                {{-- Employee Code & Manual Punch Action Button --}}
                <div class="text-md-end">
                    <div class="stat-label">Employee code</div>
                    <div class="fs-5 fw-semibold"><code>{{ $employee->employee_code }}</code></div>
                    <div class="text-md-end">
                    <div class="stat-label">phone</div>
                    <i class="bi bi-whatsapp"></i>{{ $employee->phone ?: '—' }}</div>


                 {{-- Action Button: Instant Check-In / Check-Out Punch --}}
                    @can('manage-attendance')
                        @if($canManageProfile)
                            @php
                                $checkedIn = $todayLastPunch && $todayLastPunch->status === 'in';
                            @endphp
                            <form method="POST" action="{{ route('employees.punch', $employee) }}" class="mt-2">
                                @csrf
                                @if($checkedIn)
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-fingerprint me-1"></i>Check Out
                                    </button>
                                    <div class="small text-muted mt-1">Inside since {{ $todayLastPunch->punched_at->format('g:i A') }}</div>
                                @else
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-fingerprint me-1"></i>Check In
                                    </button>
                                    <div class="small text-muted mt-1">Not checked in today</div>
                                @endif
                            </form>
                        @else
                            <button class="btn btn-secondary opacity-50 mt-2" disabled title="Punch management restricted">
                                <i class="bi bi-lock-fill me-1"></i>Punch Restricted
                            </button>
                        @endif
                    @endcan
                </div>
            </div>

            <hr>

            {{-- Employee Contact & Organization Metadata Grid --}}
            <div class="row g-3">
                <div class="col-md-3"><div class="stat-label">Email</div><div>{{ $employee->email ?: '—' }}</div></div>
                <div class="col-md-3"><div class="stat-label">Phone</div><div>{{ $employee->phone ?: '—' }}</div></div>
                <div class="col-md-3"><div class="stat-label">Joining date</div><div>{{ $employee->joining_date ? $employee->joining_date->format('M j, Y') : '—' }}</div></div>
                <div class="col-md-3"><div class="stat-label">Weekly off</div><div><i class="bi bi-calendar-week text-muted me-1"></i>{{ $employee->weekendLabel() }}</div></div>
                <div class="col-md-3"><div class="stat-label">Portal login</div><div>
                    @if($employee->user)
                        @if($employee->user->isPortalOnly())
                            <span class="badge text-bg-success-subtle text-success border border-success-subtle">Enabled</span>
                            <span class="text-muted small ms-1">{{ $employee->user->email }}</span>
                        @else
                            <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">{{ $employee->user->roleLabel() }}</span>
                            <span class="text-muted small ms-1">{{ $employee->user->email }}</span>
                        @endif
                        <a class="small d-block mt-1" href="{{ route('employees.edit', $employee) }}">Manage on edit</a>
                    @else
                        <span class="text-muted">Not enabled</span>
                        <a class="small ms-1" href="{{ route('employees.edit', $employee) }}">Enable</a>
                    @endif
                </div></div>
                <div class="col-md-3"><div class="stat-label">Current shift</div><div>
                    @if($currentShift)
                        <i class="bi bi-clock text-muted me-1"></i>{{ $currentShift->name }}
                        <span class="text-muted small">({{ $currentShift->startTimeForInput() }}–{{ $currentShift->endTimeForInput() }})</span>
                    @else
                        <span class="text-muted">Default policy</span>
                        @can('manage-employees')
                            <a class="small ms-1" href="{{ route('roster.index') }}">Assign</a>
                        @endcan
                    @endif
                </div></div>
                <div class="col-md-12"><div class="stat-label">Address</div><div>{{ $employee->address ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    {{-- This-month stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-label">Present ({{ now()->format('M') }})</div>
                <div class="stat-value">{{ $presentDays }}</div>
                <i class="bi bi-calendar-check stat-icon"></i>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-label">Late ({{ now()->format('M') }})</div>
                <div class="stat-value text-danger">{{ $lateDays }}</div>
                <i class="bi bi-alarm stat-icon"></i>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-label">Leave ({{ now()->year }})</div>
                <div class="stat-value">{{ $approvedLeaveDays }}</div>
                <i class="bi bi-calendar-heart stat-icon"></i>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card"><div class="card-body">
                <div class="stat-label">Net package</div>
                <div class="stat-value text-success">৳ {{ number_format($netPackage, 0) }}</div>
                <i class="bi bi-wallet2 stat-icon"></i>
            </div></div>
        </div>
    </div>



    <div class="row g-3">
        {{-- ========================================================= --}}
        {{-- 4. LEFT COLUMN: SALARY STRUCTURE, ROSTER & LEAVE BALANCES --}}
        {{-- ========================================================= --}}
        <div class="col-lg-5">
            {{-- Salary Structure Breakdown Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cash-stack me-1"></i>Salary</span>
                    @can('manage-payroll')
                        <a class="small" href="{{ route('payroll.salaries', ['q' => $employee->employee_code]) }}#salary-{{ $employee->id }}">Edit basic</a>
                    @endcan
                </div>
                <div class="card-body">
                    @if($basic <= 0)
                        <div class="text-muted">No salary set for this employee.</div>
                    @else
                        <table class="table table-sm mb-0">
                            <tr><th class="text-muted fw-normal">Basic salary</th><td class="text-end">৳ {{ number_format($basic, 2) }}</td></tr>
                            <tr>
                                <th class="text-muted fw-normal">Overtime / hour</th>
                                <td class="text-end">৳ {{ number_format((float) ($employee->salaryStructure->overtime_rate ?? 0), 2) }}</td>
                            </tr>
                            @foreach($employee->salaryComponents->where('type', 'earning') as $c)
                                <tr><th class="text-muted fw-normal">{{ $c->name }} @if($c->calc_type === 'percent')<span class="badge text-bg-light border">{{ rtrim(rtrim(number_format((float) $c->amount, 2), '0'), '.') }}%</span>@endif</th><td class="text-end text-success">+ ৳ {{ number_format($c->resolve($basic), 2) }}</td></tr>
                            @endforeach
                            <tr class="border-top"><th>Gross package</th><td class="text-end fw-semibold">৳ {{ number_format($grossPackage, 2) }}</td></tr>
                            @foreach($employee->salaryComponents->where('type', 'deduction') as $c)
                                <tr><th class="text-muted fw-normal">{{ $c->name }} @if($c->calc_type === 'percent')<span class="badge text-bg-light border">{{ rtrim(rtrim(number_format((float) $c->amount, 2), '0'), '.') }}%</span>@endif</th><td class="text-end text-danger">− ৳ {{ number_format($c->resolve($basic), 2) }}</td></tr>
                            @endforeach
                            <tr class="border-top"><th>Net package</th><td class="text-end fw-bold text-success">৳ {{ number_format($netPackage, 2) }}</td></tr>
                        </table>
                        <div class="form-text mt-2">Package excludes attendance-based deductions &amp; overtime, which are applied when payroll runs.</div>
                    @endif
                </div>
            </div>

            {{-- Shift Assignments Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar2-range me-1"></i>Shift assignments</span>
                    @can('manage-employees')
                        <a class="small" href="{{ route('roster.index') }}">Manage roster</a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Shift</th><th>Period</th></tr></thead>
                        <tbody>
                            @forelse($employee->shiftAssignments as $a)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($a->shift)->name ?? '—' }}</div>
                                        @if($a->shift)
                                            <div class="small text-muted">{{ $a->shift->startTimeForInput() }}–{{ $a->shift->endTimeForInput() }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $a->periodLabel() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">No shift assigned — late policy default is used.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Leave Balances Progress Card --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar2-week me-1"></i>Leave balances ({{ now()->year }})</span>
                    @can('manage-leave')<a class="small" href="{{ route('leave.balances') }}">All balances</a>@endcan
                </div>
                <div class="card-body">
                    @forelse($leaveBalances as $b)
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $b['type']->name }} @if(! $b['type']->is_paid)<span class="text-muted">(unpaid)</span>@endif</span>
                            <span><strong class="{{ $b['remaining'] < 0 ? 'text-danger' : '' }}">{{ $b['remaining'] }}</strong> left <span class="text-muted">of {{ $b['quota'] }}</span></span>
                        </div>
                        @php $pct = $b['quota'] > 0 ? min(100, (int) round($b['used'] / $b['quota'] * 100)) : ($b['used'] > 0 ? 100 : 0); @endphp
                        <div class="progress mb-3" style="height:6px;">
                            <div class="progress-bar {{ $b['remaining'] < 0 ? 'bg-danger' : 'bg-success' }}" role="progressbar" style="width: {{ $pct }}%"></div>
                        </div>
                    @empty
                        <div class="text-muted">No leave types defined.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- 5. RIGHT COLUMN: ABSENCE LOGS, RECENT PUNCHES & PAYSLIPS --}}
        {{-- ========================================================= --}}
        <div class="col-lg-7">
            {{-- Absences & Filter Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-calendar-x me-1"></i>Absences — {{ $absence['monthLabel'] }}</span>
                    <form method="GET" action="{{ route('employees.show', $employee) }}" class="d-flex align-items-center gap-2 mb-0">
                        <input type="month" name="month" value="{{ $absence['month'] }}" max="{{ now()->format('Y-m') }}"
                               class="form-control form-control-sm" style="max-width:160px" onchange="this.form.submit()">
                        <button class="btn btn-sm btn-outline-secondary no-loading" type="submit" title="Show"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="card-body pb-2">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-secondary-subtle text-secondary border">Working days: {{ $absence['workingDays'] }}</span>
                        <span class="badge text-bg-success-subtle text-success border border-success-subtle">Present: {{ $absence['presentDays'] }}</span>
                        <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">Leave: {{ $absence['leaveDays'] }}</span>
                        <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle">Absent: {{ $absence['absentCount'] }}</span>
                    </div>
                    <div class="form-text mt-1">Absent = a working day (excludes weekends &amp; holidays) with no punch and no approved leave.</div>
                </div>
                @if($absence['absentCount'] > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th style="width:3rem;">#</th><th>Absent date</th><th>Day</th></tr></thead>
                            <tbody>
                                @foreach($absence['absentDates'] as $i => $d)
                                    <tr>
                                        <td class="text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $d->format('M j, Y') }}</td>
                                        <td>{{ $d->format('l') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body pt-0 text-muted">
                        <i class="bi bi-check2-circle text-success me-1"></i>No absences recorded for this month.
                    </div>
                @endif
            </div>

            {{-- Recent Attendance Punches Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-1"></i>Recent punches</span>
                    <a class="small" href="{{ route('reports.attendance', ['employee_id' => $employee->id]) }}">View in reports</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Date</th><th>Time</th><th>In/Out</th><th>Source</th></tr></thead>
                        <tbody>
                            @forelse($recentPunches as $p)
                                @php $punchAt = \Illuminate\Support\Carbon::parse($p->punched_at); @endphp
                                <tr>
                                    <td>
                                        @can('manage-attendance')
                                            <a href="{{ route('reports.day', ['code' => $employee->employee_code, 'date' => $punchAt->toDateString()]) }}">{{ $punchAt->format('M j, Y') }}</a>
                                        @else
                                            {{ $punchAt->format('M j, Y') }}
                                        @endcan
                                    </td>
                                    <td>{{ $punchAt->format('g:i A') }}</td>
                                    <td>
                                        @if($p->status === 'in')
                                            <span class="badge text-bg-success-subtle text-success border border-success-subtle">In</span>
                                        @elseif($p->status === 'out')
                                            <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle">Out</span>
                                        @else
                                            <span class="badge text-bg-secondary-subtle text-secondary border">{{ $p->status }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ optional($p->device)->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No punches recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Leave Request History Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-heart me-1"></i>Leave history</span>
                    @can('manage-leave')
                        <a class="small" href="{{ route('leave.create', ['employee_id' => $employee->id]) }}">Add leave</a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Type</th><th>Dates</th><th class="text-center">Days</th><th>Status</th><th>Reason</th></tr></thead>
                        <tbody>
                            @forelse($leaves as $lv)
                                <tr>
                                    <td>{{ optional($lv->leaveType)->name ?? '—' }}</td>
                                    <td>
                                        {{ $lv->start_date->format('M j') }}
                                        @if($lv->end_date->ne($lv->start_date))<i class="bi bi-arrow-right mx-1 text-muted"></i>{{ $lv->end_date->format('M j, Y') }}@else, {{ $lv->start_date->format('Y') }}@endif
                                    </td>
                                    <td class="text-center">{{ $lv->days }}</td>
                                    <td>
                                        @if($lv->status === 'approved')
                                            <span class="badge text-bg-success-subtle text-success border border-success-subtle">Approved</span>
                                        @elseif($lv->status === 'rejected')
                                            <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>
                                        @else
                                            <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $lv->reason ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No leave records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Generated Payslip History Card --}}
            @can('manage-payroll')
                <div class="card">
                    <div class="card-header"><i class="bi bi-receipt me-1"></i>Payslip history</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Period</th><th class="text-end">Net salary</th><th class="text-end"></th></tr></thead>
                            <tbody>
                                @forelse($payslips as $slip)
                                    <tr>
                                        <td>{{ optional($slip->payrollRun)->periodLabel() ?? '—' }}</td>
                                        <td class="text-end fw-semibold">৳ {{ number_format((float) $slip->net_salary, 2) }}</td>
                                        <td class="text-end">
                                            @if($slip->payrollRun)
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.payslip', [$slip->payrollRun, $slip]) }}" title="View payslip"><i class="bi bi-receipt-cutoff"></i></a>
                                                <a class="btn btn-sm btn-outline-success" href="{{ route('payroll.payslip.pdf', [$slip->payrollRun, $slip]) }}" title="Download PDF"><i class="bi bi-filetype-pdf"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No payslips yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection

