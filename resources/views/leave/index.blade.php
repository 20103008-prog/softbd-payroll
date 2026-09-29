@extends('layouts.app')

@section('title', 'Leave')

@section('content')
    {{-- Page Background Color Style --}}
    <style>
        body {
            background-color: #f1f5f9 !important;
        }
    </style>

    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & NAVIGATION BUTTONS SECTION --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: #1e3a8a; -webkit-text-stroke: 1px #0f172a;">
                <i class="bi bi-calendar-heart me-2"></i>Leave
            </h1>
            <div class="text-muted hide-xs-desc" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold; color: #475569;">
                Record and approve employee leave. Approved paid leave is not deducted in payroll.
            </div>
        </div>
        <div class="page-actions">
            {{-- Leave Balances Overview Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('leave.balances') }}" style="font-weight: bold;"><i class="bi bi-calendar2-week me-1"></i><span class="d-none d-md-inline">Balances</span></a>
            {{-- Leave Types Management Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('leave-types.index') }}" style="font-weight: bold;"><i class="bi bi-tags me-1"></i><span class="d-none d-md-inline">Leave types</span><span class="d-md-none">Types</span></a>
            {{-- Holidays Management Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('holidays.index') }}" style="font-weight: bold;"><i class="bi bi-calendar-event me-1"></i><span class="d-none d-md-inline">Holidays</span></a>
            {{-- Action Button: Add New Leave Request --}}
            <a class="btn btn-primary" href="{{ route('leave.create') }}" style="font-weight: bold; background-color: #1e40af;"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">Add leave</span><span class="d-sm-none">Add</span></a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. TEAM SCOPE ALERT BANNER SECTION --}}
    {{-- ========================================================= --}}
    @if(! empty($teamScoped))
        @if(empty($managedDepartmentId))
            <div class="alert alert-warning d-flex align-items-center gap-2" style="font-weight: bold;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>Your manager account is not linked to an employee with a department, so you cannot see team leave yet. Ask an admin to link you.</div>
            </div>
        @else
            <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="font-weight: bold;">
                <i class="bi bi-info-circle-fill"></i>
                <div class="small">Showing leave for your department only.</div>
            </div>
        @endif
    @endif

    {{-- ========================================================= --}}
    {{-- 3. LEAVE STATUS COUNTER SUMMARY CARDS SECTION --}}
    {{-- ========================================================= --}}
    <div class="row g-3 mb-3">
        {{-- Pending Requests Counter --}}
        <div class="col-4">
            <a href="{{ route('leave.index', array_filter(['status' => 'pending', 'employee_id' => $filters['employee_id']])) }}" class="text-decoration-none d-block">
                <div class="card stat-card h-100" style="background-color: #fef3c7; border-left: 5px solid #f59e0b !important;">
                    <div class="card-body">
                        <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #92400e;">Pending</div>
                        <div class="stat-value text-warning" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $summary['pending'] }}</div>
                        <i class="bi bi-hourglass-split stat-icon"></i>
                    </div>
                </div>
            </a>
        </div>
        {{-- Approved Requests Counter --}}
        <div class="col-4">
            <a href="{{ route('leave.index', array_filter(['status' => 'approved', 'employee_id' => $filters['employee_id']])) }}" class="text-decoration-none d-block">
                <div class="card stat-card h-100" style="background-color: #dcfce7; border-left: 5px solid #10b981 !important;">
                    <div class="card-body">
                        <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #166534;">Approved</div>
                        <div class="stat-value text-success" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $summary['approved'] }}</div>
                        <i class="bi bi-check2-circle stat-icon"></i>
                    </div>
                </div>
            </a>
        </div>
        {{-- Rejected Requests Counter --}}
        <div class="col-4">
            <a href="{{ route('leave.index', array_filter(['status' => 'rejected', 'employee_id' => $filters['employee_id']])) }}" class="text-decoration-none d-block">
                <div class="card stat-card h-100" style="background-color: #fee2e2; border-left: 5px solid #ef4444 !important;">
                    <div class="card-body">
                        <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #991b1b;">Rejected</div>
                        <div class="stat-value text-danger" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $summary['rejected'] }}</div>
                        <i class="bi bi-x-circle stat-icon"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- 4. FILTER FORM & LEAVE REQUESTS DATA TABLE SECTION --}}
    {{-- ========================================================= --}}
    <div class="card" style="background-color: #ffffff; border: 1px solid #cbd5e1;">
        <div class="card-body" style="background-color: #e2e8f0; border-bottom: 1px solid #cbd5e1;">
            {{-- Filter Bar: By Status & Employee --}}
            <form method="GET" action="{{ route('leave.index') }}" class="row g-2 filter-bar">
                <div class="col-12 col-sm-4 col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()" style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                        <option value="">All statuses</option>
                        <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                        <option value="approved" @selected($filters['status'] === 'approved')>Approved</option>
                        <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-12 col-sm-5 col-md-4">
                    <select name="employee_id" class="form-select" onchange="this.form.submit()" style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            @if(auth()->user()->canManageEmployeeProfile($employee))
                                <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>
                                    {{ $employee->name }} ({{ $employee->employee_code }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-3 col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit" style="font-weight: bold; background-color: #1e40af;">Filter</button>
                    <a class="btn btn-outline-secondary flex-fill" href="{{ route('leave.index') }}" style="font-weight: bold;">Clear</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            {{-- Data Table: Leave Applications --}}
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th>
                            <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Type</th>
                            <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Dates</th>
                            <th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Days <i class="bi bi-info-circle text-white-50" title="Working days only — weekends and holidays are excluded"></i></th>
                            <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Status</th>
                            <th class="text-end" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                {{-- Employee Info --}}
                                <td style="background-color: #f8fafc;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar" style="background-color: #cbd5e1; color: #0f172a; font-weight: bold;">{{ strtoupper(mb_substr(optional($req->employee)->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold;">{{ optional($req->employee)->name ?? '—' }}</div>
                                            <div class="small text-muted">Code: {{ optional($req->employee)->employee_code ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- Leave Type (Paid / Unpaid) --}}
                                <td style="background-color: #f8fafc; font-weight: bold;">
                                    {{ optional($req->leaveType)->name ?? '—' }}
                                    @if($req->leaveType && ! $req->leaveType->is_paid)
                                        <span class="badge text-bg-light border text-muted" style="font-weight: bold;">unpaid</span>
                                    @endif
                                </td>
                                {{-- Date Range & Reason --}}
                                <td style="background-color: #f8fafc; font-weight: bold;">
                                    {{ $req->start_date->format('M j, Y') }}
                                    @if($req->end_date->ne($req->start_date))
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>{{ $req->end_date->format('M j, Y') }}
                                    @endif
                                    @if($req->reason)
                                        <div class="small text-muted">{{ $req->reason }}</div>
                                    @endif
                                </td>
                                {{-- Working Days Count --}}
                                <td class="text-center" style="background-color: #f8fafc; font-weight: bold;">{{ $req->days }}</td>
                                {{-- Approval Status Badge --}}
                                <td style="background-color: #f8fafc;">
                                    @if($req->status === 'approved')
                                        <span class="badge text-bg-success-subtle text-success border border-success-subtle" style="font-size: 13px; font-weight: bold;">Approved</span>
                                       
                                    @elseif($req->status === 'rejected')
                                        <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 13px; font-weight: bold;">Rejected</span>
                                    @else
                                        <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 13px; font-weight: bold;">Pending</span>
                                    @endif
                                </td>
                                 {{-- Row Action Buttons (Approve, Reject, Delete) --}}
                                <td class="text-end" style="background-color: #f8fafc;">
                                    @if(auth()->user()?->canApproveLeaveRequest($req))
                                        @if($req->status === 'pending')
                                            {{-- Action Button: Approve --}}
                                            <form method="POST" action="{{ route('leave.approve', $req) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success" title="Approve" style="font-weight: bold;"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                        @endif
                                        @if($req->status === 'pending')
                                            {{-- Action Button: Reject --}}
                                            <form method="POST" action="{{ route('leave.reject', $req) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning" title="Reject" style="font-weight: bold;"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        @endif
                                    @elseif($req->isOwnedBy(auth()->user()))
                                        <span class="badge text-bg-secondary-subtle text-secondary border me-1" title="You cannot approve or reject your own leave request" style="font-weight: bold;">Self request</span>
                                    @endif
                                     {{-- Action Button: Delete --}}
                                     @if(auth()->user()?->canDeleteLeaveRequest($req))
                                         <form method="POST" action="{{ route('leave.destroy', $req) }}" class="d-inline"
                                               onsubmit="return confirm('Delete this leave record?');">
                                             @csrf
                                             @method('DELETE')
                                             <button class="btn btn-sm btn-outline-danger" title="Delete" style="font-weight: bold;"><i class="bi bi-trash"></i></button>
                                         </form>
                                     @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4" style="background-color: #f8fafc;">
                                    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                    No leave records yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination Links --}}
            <div class="p-3" style="background-color: #e2e8f0;">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
@endsection


