@extends('layouts.app')

@section('title', 'Attendance Reports')

@php $canManage = auth()->check() && auth()->user()->can('manage-attendance'); @endphp

@section('content')
    {{-- Page Background Color Style --}}
    <style>
        body {
            background-color: #e2e8f0 !important;
        }
    </style>

    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & REPORT ACTION BUTTONS (Add Record, Export CSV) --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: #1e3a8a; -webkit-text-stroke: 1px #0f172a;">
                <i class="bi bi-clipboard-data me-2"></i>Attendance Reports
            </h1>
            <div class="text-muted hide-xs-desc" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold; color: #475569;">
                Daily check-in / check-out per employee. Filter and export to CSV.
            </div>
        </div>
        <div class="page-actions">
            {{-- Action Button: Add Attendance Record Modal Trigger --}}
            @can('manage-attendance')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal" style="font-weight: bold;">
                    <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">Add record</span><span class="d-sm-none">Add</span>
                </button>
            @endcan
            {{-- Navigation Button: Summaries --}}
            <a class="btn btn-outline-secondary" href="{{ route('reports.summary') }}" style="font-weight: bold;">
                <i class="bi bi-bar-chart-line me-1"></i><span class="d-none d-sm-inline">Summaries</span><span class="d-sm-none">Summary</span>
            </a>
            {{-- Reset Filters Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('reports.attendance') }}" style="font-weight: bold;">
                <i class="bi bi-arrow-counterclockwise me-1"></i><span class="d-none d-md-inline">Reset</span>
            </a>
            {{-- Action Button: Export Attendance CSV --}}
            <a class="btn btn-success" href="{{ route('reports.attendance.export', request()->query()) }}" style="font-weight: bold;">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i><span class="d-none d-sm-inline">Export CSV</span><span class="d-sm-none">CSV</span>
            </a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. REPORT SUMMARY STAT CARDS (Punches, Employees, Days, Late) --}}
    {{-- ========================================================= --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="background-color: #dbeafe; border-left: 5px solid #1d4ed8;">
                <div class="card-body">
                    <div class="stat-label mb-2" style="font-family: Arial, sans-serif; font-weight: bold; color: #1e40af;">Rows Matched</div>
                    <div class="stat-value text-primary" style="font-family: Arial, sans-serif; font-weight: bold;">{{ number_format($summary['total_punches']) }}</div>
                    <i class="bi bi-database stat-icon text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="background-color: #dcfce7; border-left: 5px solid #15803d;">
                <div class="card-body">
                    <div class="stat-label mb-2" style="font-family: Arial, sans-serif; font-weight: bold; color: #166534;">Employees</div>
                    <div class="stat-value text-success" style="font-family: Arial, sans-serif; font-weight: bold;">{{ number_format($summary['employees']) }}</div>
                    <i class="bi bi-people stat-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="background-color: #e0f2fe; border-left: 5px solid #0369a1;">
                <div class="card-body">
                    <div class="stat-label mb-2" style="font-family: Arial, sans-serif; font-weight: bold; color: #075985;">Days Covered</div>
                    <div class="stat-value text-info" style="font-family: Arial, sans-serif; font-weight: bold;">{{ number_format($summary['days']) }}</div>
                    <i class="bi bi-calendar3 stat-icon text-info"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="background-color: #fee2e2; border-left: 5px solid #b91c1c;">
                <div class="card-body">
                    <div class="stat-label mb-2" style="font-family: Arial, sans-serif; font-weight: bold; color: #991b1b;">Late Arrivals</div>
                    <div class="stat-value text-danger" style="font-family: Arial, sans-serif; font-weight: bold;">{{ number_format($summary['late']) }}</div>
                    <i class="bi bi-alarm stat-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>


    {{-- Late Policy Rule Info Header --}}
    <div class="d-flex align-items-center gap-2 mb-3 text-muted small" style="font-family: Arial, sans-serif; font-weight: bold;">
        <i class="bi bi-alarm"></i>
        Late after <strong>{{ \Illuminate\Support\Carbon::parse($policy->work_start_time)->format('H:i') }}</strong>
        @if($policy->grace_minutes > 0)+ {{ $policy->grace_minutes }} min grace @endif.
        @can('manage-settings')<a href="{{ route('settings.late-policy') }}">Change policy</a>@endcan
    </div>

    {{-- ========================================================= --}}
    {{-- 3. FILTER FORM CARD (From Date, To Date, Employee Select) --}}
    {{-- ========================================================= --}}
    <div class="card mb-4">
        <div class="card-body" style="background-color: #f1f5f9; border: 1px solid #cbd5e1;">
            <form method="GET" action="{{ route('reports.attendance') }}" class="row g-2 g-md-3 align-items-end filter-bar">
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #334155;"><i class="bi bi-calendar-event me-1"></i>From</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}" style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #334155;"><i class="bi bi-calendar-event me-1"></i>To</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}" style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #334155;"><i class="bi bi-person me-1"></i>Employee</label>
                    <select name="employee_id" class="form-select" style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                        <option value="">All employees</option>
                        @foreach($employees as $emp)
                            @if(auth()->user()->canManageEmployeeProfile($emp))
                                <option value="{{ $emp->id }}" @selected((string)$filters['employee_id'] === (string)$emp->id)>
                                    {{ $emp->name }} ({{ $emp->employee_code }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button class="btn btn-primary w-100" type="submit" style="font-weight: bold; background-color: #1e40af;">
                        <i class="bi bi-funnel me-1"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 4. ATTENDANCE LOGS MATRIX DATA TABLE SECTION --}}
    {{-- ========================================================= --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sticky-first">
                    <thead>
                    <tr>
                        <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Date</th>
                        <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th>
                        <th class="text-nowrap" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Check-in</th>
                        <th class="text-nowrap" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Check-out</th>
                        <th class="text-nowrap" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Worked</th>
                        <th class="text-nowrap" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Status</th>
                        <th class="text-end" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Punches</th>
                        @if($canManage)<th class="text-end" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Edit</th>@endif
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $r)
                        @php
                            $checkIn = $r->check_in ? \Illuminate\Support\Carbon::parse($r->check_in) : null;
                            $checkOut = $r->check_out ? \Illuminate\Support\Carbon::parse($r->check_out) : null;
                            $worked = ($checkIn && $checkOut) ? $checkIn->diff($checkOut) : null;
                        @endphp
                        <tr>
                            <td class="text-nowrap" style="background-color: #f8fafc; font-weight: bold;">
                                {{ \Illuminate\Support\Carbon::parse($r->work_date)->format('M d, Y') }}
                                <div class="text-muted small">{{ \Illuminate\Support\Carbon::parse($r->work_date)->format('l') }}</div>
                            </td>
                            <td style="background-color: #f8fafc;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="background-color: #cbd5e1; color: #0f172a; font-weight: bold;">{{ strtoupper(mb_substr($r->employee_name, 0, 1)) }}</span>
                                    <div>
                                        <div class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $r->employee_name }}</div>
                                        <div class="text-muted small">Code: {{ $r->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap" style="background-color: #f8fafc;">
                                @if($checkIn)
                                    <span class="badge rounded-pill text-bg-success-subtle text-success border border-success-subtle" style="font-size: 13px; font-weight: bold;">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>{{ $checkIn->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap" style="background-color: #f8fafc;">
                                @if($checkOut)
                                    <span class="badge rounded-pill text-bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 13px; font-weight: bold;">
                                        <i class="bi bi-box-arrow-right me-1"></i>{{ $checkOut->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap" style="background-color: #f8fafc; font-weight: bold;">
                                @if($worked)
                                    {{ $worked->h + ($worked->days * 24) }}h {{ $worked->i }}m
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap" style="background-color: #f8fafc;">
                                @if(! $checkIn)
                                    <span class="text-muted">—</span>
                                @elseif($r->is_half_day)
                                    <span class="badge rounded-pill text-bg-warning" style="font-weight: bold;"><i class="bi bi-pie-chart me-1"></i>Half day</span>
                                @elseif($r->late_minutes > 0)
                                    <span class="badge rounded-pill text-bg-danger" style="font-weight: bold;"><i class="bi bi-alarm me-1"></i>Late {{ $r->late_minutes }}m</span>
                                @else
                                    <span class="badge rounded-pill text-bg-success" style="font-weight: bold;"><i class="bi bi-check-circle me-1"></i>On time</span>
                                @endif
                            </td>

                            <td class="text-end" style="background-color: #f8fafc;"><span class="badge text-bg-light border" style="font-weight: bold;">{{ $r->punch_count }}</span></td>
                            @if($canManage)
                                <td class="text-end" style="background-color: #f8fafc;">
                                    @php
                                        $authUser = auth()->user();
                                        $authRole = $authUser?->role;
                                        $isSelf = $authUser?->employee?->employee_code === $r->employee_code;
                                        $targetRole = $r->target_user_role ?? null;
                                        $isRestricted = false;

                                        // 1. Cannot edit own attendance
                                        if ($isSelf) {
                                            $isRestricted = true;
                                        }

                                        // 2. HR Manager cannot edit Admin or Super Admin attendance
                                        if ($authRole === \App\Models\User::ROLE_HR && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
                                            $isRestricted = true;
                                        }

                                        // 3. Manager cannot edit Admin, Super Admin, or HR Manager attendance
                                        if ($authRole === \App\Models\User::ROLE_MANAGER && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_HR], true)) {
                                            $isRestricted = true;
                                        }
                                    @endphp
                                    @if($isRestricted)
                                        <button class="btn btn-sm btn-outline-secondary opacity-50" disabled title="You cannot edit this attendance record">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @else
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('reports.day', ['code' => $r->employee_code, 'date' => \Illuminate\Support\Carbon::parse($r->work_date)->format('Y-m-d')]) }}"
                                           title="Edit this day's punches" style="font-weight: bold;">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 8 : 7 }}" class="text-center py-5 text-muted" style="background-color: #f8fafc;">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No records found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($rows, 'hasPages') && $rows->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center" style="background-color: #e2e8f0;">
                <span class="text-muted small" style="font-weight: bold;">Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}</span>
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    @can('manage-attendance')
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('reports.punches.store') }}">
                    @csrf
                    <div class="modal-header" style="background-color: #1e40af; color: white;">
                        <h5 class="modal-title" style="font-family: Arial, sans-serif; font-weight: bold;"><i class="bi bi-plus-circle me-2"></i>Add attendance record</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: #f8fafc;">
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: bold; color: #1e3a8a;">Employee</label>
                            <select name="employee_code" class="form-select" required style="background-color: #ffffff; font-weight: bold;">
                                <option value="">Select employee…</option>
                                @foreach($employees as $emp)
                                    @if(auth()->user()->canManageEmployeeProfile($emp))
                                        <option value="{{ $emp->employee_code }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Only registered employees are listed. Add people under <a href="{{ route('employees.index') }}">Employees</a>.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-7">
                                <label class="form-label" style="font-weight: bold; color: #1e3a8a;">Date &amp; time</label>
                                <input type="datetime-local" name="punched_at" class="form-control"
                                       value="{{ ($filters['from'] ?: now()->toDateString()) }}T09:00" required style="background-color: #ffffff; font-weight: bold;">
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label" style="font-weight: bold; color: #1e3a8a;">Status</label>
                                <select name="status" class="form-select" style="background-color: #ffffff; font-weight: bold;">
                                    <option value="in">In</option>
                                    <option value="out">Out</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8fafc;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="font-weight: bold; background-color: #1e40af;"><i class="bi bi-check-lg me-1"></i>Add record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
@endsection

