@extends('layouts.app')

@section('title', 'Employee Salaries')

@section('content')
    {{-- Page Background Color Style --}}
    <style>
        body {
            background-color: #f1f5f9 !important;
        }
    </style>

    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & BACK BUTTON SECTION --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: #1e3a8a; -webkit-text-stroke: 1px #0f172a;">
                <i class="bi bi-wallet2 me-2"></i>Employee Salaries
            </h1>
            <div class="text-muted hide-xs-desc" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold; color: #475569;">
                Set each employee's monthly salary and overtime rate (per hour).
            </div>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('payroll.index') }}" style="font-weight: bold;">
                <i class="bi bi-arrow-left me-1"></i><span class="d-none d-sm-inline">Back to payroll</span><span class="d-sm-none">Back</span>
            </a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. VALIDATION ERROR ALERTS SECTION --}}
    {{-- ========================================================= --}}
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2" style="font-weight: bold;">
            <i class="bi bi-x-circle-fill"></i>
            <div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- 3. SEARCH & SALARIES DATA TABLE CARD SECTION --}}
    {{-- ========================================================= --}}
    <div class="card" style="background-color: #ffffff; border: 1px solid #cbd5e1;">
        <div class="card-body" style="background-color: #e2e8f0; border-bottom: 1px solid #cbd5e1;">
            <form method="GET" action="{{ route('payroll.salaries') }}" class="row g-2 filter-bar">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text" style="background-color: #cbd5e1;"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search name or code" value="{{ $search }}" style="background-color: #ffffff; font-weight: bold;">
                        <button class="btn btn-primary" type="submit" style="font-weight: bold; background-color: #1e40af;">Search</button>
                        @if($search !== '')
                            <a class="btn btn-outline-secondary" href="{{ route('payroll.salaries') }}" style="font-weight: bold;">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-sticky-first">
                    <thead>
                        <tr>
                            <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th>
                            <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Code</th>
                            <th style="width: 180px; background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Monthly salary (৳)</th>
                            <th style="width: 180px; background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Overtime / hour (৳)</th>
                            <th style="width: 190px; background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $authUser = auth()->user();
                            $authRole = $authUser?->role;
                            $authEmpId = $authUser?->employee_id;
                        @endphp
                        @forelse($employees as $employee)
                            @php
                                $s = $employee->salaryStructure;
                                $isSelf = ($authEmpId && $employee->id == $authEmpId);
                                $targetRole = $employee->user?->role;
                                $isRestricted = false;

                                // 1. Anyone cannot edit their own salary
                                if ($isSelf) {
                                    $isRestricted = true;
                                }

                                // 2. HR Manager cannot edit Admin or Super Admin salary
                                if ($authRole === \App\Models\User::ROLE_HR && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
                                    $isRestricted = true;
                                }

                                // 3. Manager cannot edit Admin, Super Admin, or HR Manager salary
                                if ($authRole === \App\Models\User::ROLE_MANAGER && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_HR], true)) {
                                    $isRestricted = true;
                                }
                            @endphp
                            <tr id="salary-{{ $employee->id }}">
                                <td style="background-color: #f8fafc;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar" style="background-color: #cbd5e1; color: #0f172a; font-weight: bold;">{{ strtoupper(mb_substr($employee->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $employee->name }}</div>
                                            @if($employee->department)
                                                <div class="small text-muted">{{ $employee->department->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="background-color: #f8fafc;"><span class="badge text-bg-light border" style="font-weight: bold;">{{ $employee->employee_code }}</span></td>
                                <td style="background-color: #f8fafc;">
                                    <input type="number" step="0.01" min="0" name="monthly_salary" form="sal-{{ $employee->id }}"
                                           class="form-control" value="{{ $s->monthly_salary ?? '0.00' }}" required
                                           @disabled($isRestricted) style="background-color: #ffffff; font-weight: bold;">
                                </td>
                                <td style="background-color: #f8fafc;">
                                    <input type="number" step="0.01" min="0" name="overtime_rate" form="sal-{{ $employee->id }}"
                                           class="form-control" value="{{ $s->overtime_rate ?? '0.00' }}"
                                           @disabled($isRestricted) style="background-color: #ffffff; font-weight: bold;">
                                </td>
                                <td class="text-nowrap text-end" style="background-color: #f8fafc;">
                                    @if(! $isRestricted)
                                        <button class="btn btn-sm btn-primary" type="submit" form="sal-{{ $employee->id }}" style="font-weight: bold; background-color: #1e40af;">
                                            <i class="bi bi-check-lg me-1"></i>Save
                                        </button>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('payroll.components', $employee) }}" title="Allowances, bonuses, tax, PF" style="font-weight: bold;">
                                            <i class="bi bi-sliders"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary opacity-50" disabled title="You cannot modify this salary structure" style="font-weight: bold;">
                                            <i class="bi bi-lock-fill me-1"></i>Locked
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4" style="background-color: #f8fafc;">
                                    <i class="bi bi-people fs-3 d-block mb-2"></i>
                                    No employees found. <a href="{{ route('employees.index') }}">Add employees</a> first.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3" style="background-color: #e2e8f0;">
                {{ $employees->links() }}
            </div>
        </div>
    </div>

    {{-- Row forms live outside the table so the markup stays valid HTML. --}}
    @foreach($employees as $employee)
        <form id="sal-{{ $employee->id }}" method="POST" action="{{ route('payroll.salaries.update', $employee) }}" class="d-none">
            @csrf
            <input type="hidden" name="q" value="{{ $search }}">
        </form>
    @endforeach
@endsection

