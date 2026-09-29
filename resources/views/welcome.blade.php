@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $firstName = auth()->user()?->name ? explode(' ', trim(auth()->user()->name))[0] : 'there';
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $devicePct = $stats['devices_total'] > 0
        ? (int) round(($stats['devices_active'] / $stats['devices_total']) * 100)
        : 0;
    $headline = $holidayToday
        ? 'Holiday today — attendance targets are paused.'
        : ($stats['attendance_rate'] !== null
            ? $stats['attendance_rate'].'% of expected staff have punched in so far.'
            : 'Here’s a live snapshot of attendance and HR work.');
@endphp
 {{-- PAGE Background color section start --}}
    <style>
    body {
        background-color: #c6f5f8ff !important; /* */
    }
</style>

{{-- PAGE Background color section end --}}

    <div class="page-toolbar">
        <div class="min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2"style="font-family: Arial, 'Times New Roman', calibri, serif; font-size: 18px; font-style: italic; font-weight: bold; background-color: #ebebf5ff; color: black;">
                <span class="smart-chip">
                    <span class="dot"></span>
                    {{ now()->format('D, M j') }}
                </span>
                @if($holidayToday)
                    <span class="smart-chip warn">
                        <span class="dot"></span>
                        Holiday: {{ $holidayToday->name }}
                    </span>
                @elseif($stats['attendance_rate'] !== null)
                    <span class="smart-chip {{ $stats['attendance_rate'] >= 90 ? '' : ($stats['attendance_rate'] >= 70 ? 'warn' : 'danger') }}">
                        <span class="dot"></span>
                        {{ $stats['attendance_rate'] }}% attendance
                    </span>
                @endif
                @if(($pendingLeaveCount ?? 0) > 0)
                    <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="smart-chip warn text-decoration-none">
                        <span class="dot"></span>
                        {{ $pendingLeaveCount }} pending leave
                    </a>
                @endif
                @if($stats['last_punch_at'])
                    <span class="smart-chip hide-xs-desc">
                        <i class="bi bi-clock-history"></i>
                        Last punch {{ \Illuminate\Support\Carbon::parse($stats['last_punch_at'])->diffForHumans() }}
                    </span>
                @endif
            </div>
            <h1 class="h3 fw-semibold mb-1"style="font-family: Arial, sans-serif; font-size: 30px; font-style: italic; font-weight: bold; color:gray;-webkit-text-stroke: 2px #064952ff;">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="text-muted mb-0">{{ $headline }}</p>
        </div>
        <div class="page-actions">
            @can('view-reports')
                <a class="btn btn-sm btn-primary" href="{{ route('reports.attendance', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">
                    <i class="bi bi-calendar-day me-1"></i>Today’s report
                </a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.summary') }}">
                    <i class="bi bi-bar-chart-line me-1"></i>Summaries
                </a>
            @endcan
        </div>
    </div>

    {{-- Today at a glance --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body"style="background-color: #d1a9bdff; color: white;">
                    
                    <div class="stat-label mb-2"style="font-family: Arial, sans-serif; font-size: 20px; font-weight: bold; color: black;">Present today</div>
                    <div class="stat-value text-success"style="font-family: Arial, sans-serif; font-size: 18px; font-weight: bold;  color: black;">{{ $stats['present_today'] }}</div>
                    <div class="stat-hint"style="font-family: Arial, sans-serif; font-size:5x; font-weight: bold;  color: black;">
                        @if($stats['expected_today'] > 0)
                            of {{ $stats['expected_today'] }} expected
                        @else
                            {{ $holidayToday ? 'Holiday / weekend' : 'No one expected' }}
                        @endif
                    </div>
                    @if($stats['attendance_rate'] !== null)
                        <div class="stat-progress" title="{{ $stats['attendance_rate'] }}%"><span style="width: {{ $stats['attendance_rate'] }}%"></span></div>
                    @endif
                    <i class="bi bi-person-check stat-icon"style="background-color: #44e66cff; color: black;"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            @can('view-reports')
                <a class="card stat-card card-hover h-100" href="{{ route('reports.summary', ['tab' => 'late', 'from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">
            @else
                <div class="card stat-card h-100">
            @endcan
                <div class="card-body"style="font-family: Arial, sans-serif; font-size: 20px;  font-weight: bold; background-color: #8b8a8bff; color: black;">
                    <div class="stat-label mb-2"style="font-family: Arial, sans-serif; font-size: 18px; font-weight: bold; color: black;">Late today</div>
                    <div class="stat-value {{ $stats['late_today'] > 0 ? 'text-warning' : 'text-body-emphasis' }}"style="font-family: Arial, sans-serif; font-size: 18px;  font-weight: bold;  color: black;">{{ $stats['late_today'] }}</div>
                    <div class="stat-hint">{{ $stats['late_today'] > 0 ? 'Needs attention' : 'On track' }}</div>
                    <i class="bi bi-alarm stat-icon"></i>
                </div>
            @can('view-reports')
                </a>
            @else
                </div>
            @endcan
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body"style="font-family: calibri, sans-serif;  font-weight: bold; background-color: #c9ccadff; ">
                    <div class="stat-label mb-2"style="font-family: calibri, sans-serif; font-size: 20px; font-weight: bold;color: blue;" >On leave</div>
                    <div class="stat-value"style="font-family: calibri, sans-serif; font-size: 18px; font-weight: bold; color:deeppink;">{{ $stats['on_leave_today'] }}</div>
                    <div class="stat-hint">Approved for today</div>
                    <i class="bi bi-calendar-heart stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100"style="background-color: #fffffeff ;">
                <div class="card-body">
                    
                    <div class="stat-label mb-2"style="font-family: calibri, sans-serif; font-size: 20px;  font-weight: bold;  color:purple;">Not punched yet</div>
                    <div class="stat-value {{ $stats['missing_today'] > 0 ? 'text-danger' : 'text-body-emphasis' }}"style="font-family: calibri, sans-serif; font-size: 18px; font-weight: bold; color:purple;">{{ $stats['missing_today'] }}</div>
                    <div class="stat-hint">Expected but silent</div>
                    <i class="bi bi-person-x stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary ops metrics --}}
    <div class="row g-3 mb-4">





        {{--DEVICE Collumn
        <div class="col-6 col-lg-3">
            @can('manage-devices')
                <a class="card stat-card card-hover h-100" href="{{ route('devices.index') }}">
            @else
                <div class="card stat-card h-100">
            @endcan
                <div class="card-body">
                    <div class="stat-label mb-2">Active devices</div>
                    <div class="stat-value text-primary">{{ $stats['devices_active'] }}<span class="fs-6 text-muted fw-normal"> / {{ $stats['devices_total'] }}</span></div>
                    <div class="stat-progress"><span style="width: {{ $devicePct }}%"></span></div>
                    <i class="bi bi-hdd-network stat-icon"></i>
                </div>
            @can('manage-devices')
                </a>
            @else
                </div>
            @endcan
        </div>
        --}}




        <div class="col-6 col-lg-3">
            @can('manage-employees')
                <a class="card stat-card card-hover h-100" href="{{ route('employees.index') }}">
            @else
                <div class="card stat-card h-100">
            @endcan
                <div class="card-body"style="background-color: #aaaee9ff ;">
                    
                    <div class="stat-label mb-2"style="font-family: Arial, sans-serif; font-size: 20px; font-weight: bold;  color: black;">Active employees</div>
                    <div class="stat-value text-success"style="font-family: Arial, sans-serif; font-size: 18px; font-weight: bold; color: blue;">{{ $stats['employees_total'] }}</div>
                    <div class="stat-hint">In your scope</div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
            @can('manage-employees')
                </a>
            @else
                </div>
            @endcan
        </div>
        <div class="col-6 col-lg-3">
            @can('view-reports')
                <a class="card stat-card card-hover h-100" href="{{ route('reports.attendance', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">
            @else
                <div class="card stat-card h-100">
            @endcan
                <div class="card-body"style="background-color: #9dd6b0ff ;">
                    <div class="stat-label mb-2"style="font-family: Arial, sans-serif; font-size: 20px; font-weight: bold;  border: 2px solid #9da714ff; color:black;">Punches today</div>
                    <div class="stat-value"style="font-family: Arial, sans-serif; font-size: 18px; font-weight: bold; color: black;">{{ $stats['punches_today'] }}</div>
                    <div class="stat-hint">
                        @if($stats['punch_delta'] > 0)
                            <span class="text-success">+{{ $stats['punch_delta'] }} vs yesterday</span>
                        @elseif($stats['punch_delta'] < 0)
                            <span class="text-danger">{{ $stats['punch_delta'] }} vs yesterday</span>
                        @else
                            Same as yesterday
                        @endif
                    </div>
                    <i class="bi bi-fingerprint stat-icon"></i>
                </div>
            @can('view-reports')
                </a>
            @else
                </div>
            @endcan
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body"style="background-color: #d0c7e6ff ;">
                    <div class="stat-label mb-2"style="font-family: Arial, sans-serif; font-size: 20px; font-weight: bold;  color:black;">Pending leave</div>
                    <div class="stat-value {{ $pendingLeaveCount > 0 ? 'text-warning' : 'text-body-emphasis' }}"style="font-family: Arial, sans-serif; font-size: 18px; font-weight: bold; color: black;">{{ $pendingLeaveCount }}</div>
                    <div class="stat-hint">Awaiting approval</div>
                    <i class="bi bi-inbox stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Smart insights --}}
        <div class="col-lg-5"style="background-color: #ecbcbcff ;">
            <div class="card h-100"style="background-color: #b4e4e1ff ;">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-lightbulb me-1"></i>Smart insights</span>
                </div>
                <div class="card-body">
                    @foreach($insights as $insight)
                        @php
                            $toneClass = match($insight['tone']) {
                                'ok' => 'text-success',
                                'warn' => 'text-warning',
                                'danger' => 'text-danger',
                                default => 'text-primary',
                            };
                            $bg = match($insight['tone']) {
                                'ok' => 'rgba(34,197,94,.08)',
                                'warn' => 'rgba(245,158,11,.10)',
                                'danger' => 'rgba(239,68,68,.08)',
                                default => 'var(--accent-soft)',
                            };
                        @endphp
                        <div class="d-flex gap-3 @if(! $loop->last) mb-3 pb-3 border-bottom @endif">
                            <span class="avatar" style="background: {{ $bg }}; color: inherit;">
                                <i class="bi {{ $insight['icon'] }} {{ $toneClass }}"></i>
                            </span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold">{{ $insight['title'] }}</div>
                                <div class="small text-muted">{{ $insight['detail'] }}</div>
                                @if(! empty($insight['href']))
                                    <a class="small text-decoration-none" href="{{ $insight['href'] }}">Open <i class="bi bi-arrow-right"></i></a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 7-day trend --}}
        <div class="col-lg-7">
            <div class="card h-100"style="background-color: #d8acb7ff ;">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-activity me-1"></i>Punch volume · last 7 days</span>
                    <span class="small text-muted">Today {{ $stats['punches_today'] }} punches</span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-end justify-content-between gap-2" style="min-height: 140px;">
                        @foreach($weekTrend as $day)
                            @php
                                $h = max(8, (int) round(($day['count'] / $trendMax) * 110));
                            @endphp
                            <div class="text-center flex-fill">
                                <div class="small text-muted mb-1">{{ $day['count'] }}</div>
                                <div class="mx-auto rounded-2"
                                     style="width: 70%; max-width: 36px; height: {{ $h }}px; background: {{ $day['is_today'] ? 'var(--accent)' : 'color-mix(in srgb, var(--accent) 35%, var(--border))' }};"
                                     title="{{ $day['date'] }}: {{ $day['count'] }} punches"></div>
                                <div class="small fw-semibold mt-2 {{ $day['is_today'] ? 'text-primary' : 'text-muted' }}">{{ $day['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Missing punches --}}
        <div class="col-lg-6">
            <div class="card h-100"style="background-color: #a8d5ffff ;">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-person-x me-1"></i>Still not punched</span>
                    @can('view-reports')
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.attendance', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">Today</a>
                    @endcan
                </div>
                <div class="card-body">
                    @forelse($missingToday as $emp)
                        <div class="activity-row">
                            <span class="avatar">{{ strtoupper(mb_substr($emp->name, 0, 1)) }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">
                                    @can('manage-employees')
                                        <a class="text-decoration-none" href="{{ route('employees.show', $emp) }}">{{ $emp->name }}</a>
                                    @else
                                        {{ $emp->name }}
                                    @endcan
                                </div>
                                <div class="small text-muted">Code {{ $emp->employee_code }}@if($emp->department) · {{ $emp->department->name }}@endif</div>
                            </div>
                            <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle">Missing</span>
                        </div>
                    @empty
                        <div class="empty-state py-3">
                            <i class="bi bi-check2-circle text-success"></i>
                            @if($holidayToday)
                                Holiday today — no missing punches to chase.
                            @elseif($stats['expected_today'] === 0)
                                Nobody is expected in today.
                            @else
                                Everyone expected has punched in. Nice.
                            @endif
                        </div>
                    @endforelse
                    @if($stats['missing_today'] > $missingToday->count())
                        <div class="small text-muted mt-2">Showing {{ $missingToday->count() }} of {{ $stats['missing_today'] }}.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- On leave today --}}
        <div class="col-lg-6">
            <div class="card h-100" style="background-color: #9b8d94ff; color: white;">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-calendar-heart me-1"style="background-color: #eebc19ff; color: white;"></i>On leave today</span>
                    @can('manage-leave')
                        <a class="btn btn-sm btn-outline-secondary" style="background-color: #ecc71eff; color: black;" href="{{ route('leave.index', ['status' => 'approved']) }}">All leave</a>
                    @endcan
                </div>
                <div class="card-body">
                    @forelse($onLeaveToday as $lv)
                        <div class="activity-row">
                            <span class="avatar">{{ strtoupper(mb_substr(optional($lv->employee)->name ?? '?', 0, 1)) }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ optional($lv->employee)->name ?? '—' }}</div>
                                <div class="small text-muted text-truncate">
                                    {{ optional($lv->leaveType)->name ?? 'Leave' }}
                                    · {{ $lv->start_date->format('M j') }}@if($lv->end_date->ne($lv->start_date))–{{ $lv->end_date->format('M j') }}@endif
                                </div>
                            </div>
                            <span class="badge text-bg-info-subtle text-info border border-info-subtle">{{ $lv->days }}d</span>
                        </div>
                    @empty
                        <div class="empty-state py-3">
                            <i class="bi bi-emoji-smile"></i>
                            Nobody is on approved leave today.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @canany(['manage-leave', 'manage-payroll'])
        <div class="row g-3 mb-4">
            @can('manage-leave')
                <div class="col-lg-6">
                    <div class="card h-100"style="background-color: #7decd9ff; color: black;">
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="d-flex align-items-center gap-2">
                                <i class="bi bi-hourglass-split"style="background-color: #f8bbcfff; color: black;"></i>Pending approvals
                                @if(($pendingLeaveCount ?? 0) > 0)
                                    <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">{{ $pendingLeaveCount }}</span>
                                @endif
                            </span>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('leave.index', ['status' => 'pending']) }}">Review all</a>
                        </div>


                        <div class="card-body"style="background-color: #ecdda6ff; color: black ;">
                            @forelse($pendingLeaves as $lv)
                                <div class="activity-row"style="background-color: #e6daceff; color: black;">
                                    <span class="avatar"style="background-color: #5c5c2fff; color: black;">{{ strtoupper(mb_substr(optional($lv->employee)->name ?? '?', 0, 1)) }}</span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate">{{ optional($lv->employee)->name ?? '—' }}</div>
                                        <div class="small text-muted text-truncate">
                                            {{ optional($lv->leaveType)->name ?? 'Leave' }} &middot;
                                            {{ $lv->start_date->format('M j') }}@if($lv->end_date->ne($lv->start_date))–{{ $lv->end_date->format('M j') }}@endif
                                            ({{ $lv->days }}d)
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1"style="background-color: #c8eef0ff; color: black;">
                                        @if(auth()->user()?->canApproveLeaveRequest($lv))
                                            <form method="POST" action="{{ route('leave.approve', $lv) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success"title="Approve"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('leave.reject', $lv) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger" title="Reject"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        @elseif($lv->isOwnedBy(auth()->user()))
                                            <span class="badge text-bg-secondary-subtle text-secondary border small align-self-center">Self request</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state py-3">
                                    <i class="bi bi-check2-circle text-success"></i>
                                    No pending leave requests.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endcan

            @can('manage-payroll')
                <div class="col-lg-6">
                    <div class="card h-100"style="background-color: #f7e5d2ff; color: black;">
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span><i class="bi bi-cash-coin me-1"></i>Latest payroll</span>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('payroll.index') }}"style="background-color: #42163fff; color: red;">All payroll</a>
                        </div>
                        <div class="card-body">
                            @if($latestPayroll)
                                <div class="d-flex align-items-baseline justify-content-between mb-1 gap-2 flex-wrap">
                                    <div class="fw-semibold">{{ $latestPayroll->periodLabel() }}</div>
                                    @if($latestPayroll->isLocked())
                                        <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">Locked</span>
                                    @elseif($latestPayroll->status === 'generated')
                                        <span class="badge text-bg-success-subtle text-success border border-success-subtle">Generated</span>
                                    @else
                                        <span class="badge text-bg-secondary-subtle text-secondary border">Draft</span>
                                    @endif
                                </div>
                                <div class="stat-value text-success mb-1">৳ {{ number_format((float) ($latestPayroll->payslips_sum_net_salary ?? 0), 0) }}</div>
                                <div class="text-muted small mb-3">Net payable across {{ $latestPayroll->payslips_count }} payslip(s)</div>
                                <a class="btn btn-sm btn-primary" href="{{ route('payroll.show', $latestPayroll) }}"><i class="bi bi-eye me-1"></i>View run</a>
                            @else
                                <div class="empty-state py-3">
                                    <i class="bi bi-inbox"></i>
                                    No payroll generated yet.
                                    <div class="mt-3">
                                        <a class="btn btn-sm btn-primary" href="{{ route('payroll.index') }}"><i class="bi bi-gear-wide-connected me-1"></i>Generate payroll</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    @endcanany

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100"style="background-color: #9ef8d6ff ;">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="bi bi-clock-history me-1"></i>Recent punches</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.attendance') }}">View all</a>
                  
                </div>
                <div class="card-body p-0"
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="background-color: #b0ffadff; color: black;">Employee</th>
                                <th style="background-color: #e7c0ccc2; color: black;">Punched at</th>
                                <th class="text-end"style="background-color: #e6e2dbff ;">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recent as $r)
                                @php $name = $r->employee_name ?: ('#'.$r->employee_code); @endphp
                                <tr>
                                    <td style="background-color: #c2ffcfff ;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
                                            <div>
                                                <div class="fw-semibold">{{ $name }}</div>
                                                <div class="text-muted small">Code: {{ $r->employee_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="background-color: #f0d6d9ff;">
                                        <div class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($r->punched_at)->format('M d, Y H:i') }}</div>
                                        <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->punched_at)->diffForHumans() }}</div>
                                    </td>
                                    <td class="text-end"style="background-color: #e3e4beff ;">
                                        @if($r->display_status === 'in')
                                            <span class="badge rounded-pill text-bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-box-arrow-in-right me-1"></i>In
                                            </span>
                                        @else
                                            <span class="badge rounded-pill text-bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-box-arrow-right me-1"></i>Out
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            No punches yet. Configure a device or send a test push.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"style="background-color: #95ad7aff; color: black;"><i class="bi bi-lightning-charge me-1"></i>Suggested next steps</div>
                <div class="card-body d-flex flex-column gap-2">
                    @if($pendingLeaveCount > 0)
                        <a class="card card-hover text-decoration-none"style="background-color: #cec0bfff; color: black;" href="{{ route('leave.index', ['status' => 'pending']) }}">
                            <div class="card-body d-flex align-items-start gap-3 py-3">
                                <span class="avatar"><i class="bi bi-hourglass-split"></i></span>
                                <div>
                                    <div class="fw-semibold text-body">Clear {{ $pendingLeaveCount }} pending leave</div>
                                    <div class="small text-muted">Keep balances and payroll accurate.</div>
                                </div>
                            </div>
                        </a>
                    @endif
                    @if($stats['missing_today'] > 0 && ! $holidayToday)
                        @can('view-reports')
                        <a class="card card-hover text-decoration-none"style="background-color: #f0f19fff; color: black;" href="{{ route('reports.attendance', ['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">
                            <div class="card-body d-flex align-items-start gap-3 py-3">
                                <span class="avatar"><i class="bi bi-person-x"></i></span>
                                <div>
                                    <div class="fw-semibold text-body">Follow up {{ $stats['missing_today'] }} missing punches</div>
                                    <div class="small text-muted">Open today’s attendance and check devices.</div>
                                </div>
                            </div>
                        </a>
                        @endcan
                    @endif
                    @if($stats['late_today'] > 0)
                        @can('view-reports')
                        <a class="card card-hover text-decoration-none"style="background-color: #d2c9f3ff; color: black;" href="{{ route('reports.summary', ['tab' => 'late', 'from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">
                            <div class="card-body d-flex align-items-start gap-3 py-3">
                                <span class="avatar"><i class="bi bi-alarm"></i></span>
                                <div>
                                    <div class="fw-semibold text-body">Review {{ $stats['late_today'] }} late arrivals</div>
                                    <div class="small text-muted">Open the late summary for today.</div>
                                </div>
                            </div>
                        </a>
                        @endcan
                    @endif
                    @can('manage-payroll')
                        @if($latestPayroll && ! $latestPayroll->isLocked())
                            <a class="card card-hover text-decoration-none"style="background-color: #eca8cdff; color: black;" href="{{ route('payroll.show', $latestPayroll) }}">
                                <div class="card-body d-flex align-items-start gap-3 py-3">
                                    <span class="avatar"><i class="bi bi-cash-coin"></i></span>
                                    <div>
                                        <div class="fw-semibold text-body">Finish {{ $latestPayroll->periodLabel() }} payroll</div>
                                        <div class="small text-muted">Review payslips and lock when ready.</div>
                                    </div>
                                </div>
                            </a>
                        @endif
                    @endcan
                    <button type="button" class="card card-hover text-start border w-100"style="background-color: #fad5c3ff; color: black;" id="dashOpenSearch" style="background:var(--surface);">
                        <div class="card-body d-flex align-items-start gap-3 py-3">
                            <span class="avatar"><i class="bi bi-search"></i></span>
                            <div>
                                <div class="fw-semibold text-body">Quick jump <kbd class="nav-kbd"style="background-color: #deffb4ff; color: black;">Ctrl+K</kbd></div>
                                <div class="small text-muted">Search any page instantly.</div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var btn = document.getElementById('dashOpenSearch');
        var openBtn = document.getElementById('navSearchBtn');
        if (btn && openBtn) {
            btn.addEventListener('click', function () { openBtn.click(); });
        }
    })();
</script>
@endpush
