@extends('layouts.app')

@section('title', 'Payroll — '.$run->periodLabel())

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & ACTION BUTTONS (Back, Export CSV, Lock/Unlock) --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2"></i>Payroll — {{ $run->periodLabel() }}</h1>
            <div class="text-muted hide-xs-desc">
                {{ $run->working_days }} working days &middot; {{ rtrim(rtrim(number_format((float) $run->standard_daily_hours, 2), '0'), '.') }}h/day standard
                @if($run->generated_at)
                    &middot; generated {{ $run->generated_at->format('M j, Y g:i A') }}
                @endif
                @if($run->isLocked() && $run->locked_at)
                    &middot; locked {{ $run->locked_at->format('M j, Y g:i A') }}
                @endif
            </div>
        </div>
        <div class="page-actions">
            {{-- Navigation Back Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('payroll.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
            {{-- Action Button: Export Payroll CSV --}}
            <a class="btn btn-outline-success" href="{{ route('payroll.export', $run) }}"><i class="bi bi-filetype-csv me-1"></i><span class="d-none d-sm-inline">Export CSV</span><span class="d-sm-none">CSV</span></a>
            {{-- Action Button: Lock or Unlock Payroll Run --}}
            @if($run->isLocked())
                <form method="POST" action="{{ route('payroll.unlock', $run) }}" onsubmit="return confirm('Unlock this payroll so it can be regenerated?');">
                    @csrf
                    <button class="btn btn-outline-warning" type="submit"><i class="bi bi-unlock me-1"></i>Unlock</button>
                </form>
            @else
                <form method="POST" action="{{ route('payroll.lock', $run) }}" onsubmit="return confirm('Lock payroll for {{ $run->periodLabel() }}? It cannot be regenerated until unlocked.');">
                    @csrf
                    <button class="btn btn-primary" type="submit"><i class="bi bi-lock me-1"></i><span class="d-none d-sm-inline">Lock payroll</span><span class="d-sm-none">Lock</span></button>
                </form>
            @endif
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. PAYROLL SUMMARY STATS CARDS (Gross, Deductions, Overtime, Net) --}}
    {{-- [CARD COLOR CUSTOMIZATION]: --}}
    {{-- Add color class to <div class="card stat-card">: card-primary | card-success | card-danger | card-warning | card-info | card-purple --}}
    {{-- Or gradient class: card-gradient-primary | card-gradient-success | card-gradient-danger | card-gradient-purple --}}
    {{-- ========================================================= --}}
    <div class="row g-3 mb-3">
        {{-- Gross Total Stat Card (Customizable Color) --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card card-primary"><div class="card-body">
                <div class="stat-label">Gross total</div>
                <div class="stat-value text-primary">৳ {{ number_format((float) $totals['gross'], 0) }}</div>
                <i class="bi bi-cash stat-icon"></i>
            </div></div>
        </div>
        {{-- Deductions Total Stat Card (Customizable Color) --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card card-danger"><div class="card-body">
                <div class="stat-label">Deductions</div>
                <div class="stat-value text-danger">৳ {{ number_format((float) $totals['deductions'], 0) }}</div>
                <i class="bi bi-dash-circle stat-icon"></i>
            </div></div>
        </div>
        {{-- Overtime Total Stat Card (Customizable Color) --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card card-info"><div class="card-body">
                <div class="stat-label">Overtime</div>
                <div class="stat-value text-info">৳ {{ number_format((float) $totals['overtime'], 0) }}</div>
                <i class="bi bi-clock-history stat-icon"></i>
            </div></div>
        </div>
        {{-- Net Payable Stat Card (Customizable Color) --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card card-success"><div class="card-body">
                <div class="stat-label">Net payable</div>
                <div class="stat-value text-success">৳ {{ number_format((float) $totals['net'], 0) }}</div>
                <i class="bi bi-wallet2 stat-icon"></i>
            </div></div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- 3. PAYSLIPS DATA TABLE & INDIVIDUAL ACTIONS --}}
    {{-- ========================================================= --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-receipt me-1"></i>Payslips</span>
            <span class="text-muted small">{{ $run->payslips->count() }} employee(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-sticky-first">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Late</th>
                        <th class="text-end">OT (hrs)</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($run->payslips as $slip)
                        <tr>
                            {{-- Employee Name & Code --}}
                            <td class="d-flex align-items-center gap-2">
                                <span class="avatar">{{ strtoupper(mb_substr($slip->employee_name, 0, 1)) }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $slip->employee_name }}</div>
                                    <div class="small text-muted">Code: {{ $slip->employee_code }}</div>
                                </div>
                            </td>
                            {{-- Attendance Days & Paid Leaves --}}
                            <td class="text-center">
                                {{ $slip->present_days }}/{{ $slip->working_days }}
                                @if($slip->paid_leave_days > 0)
                                    <span class="badge text-bg-info-subtle text-info border border-info-subtle" title="Paid leave days">+{{ $slip->paid_leave_days }} leave</span>
                                @endif
                            </td>
                            {{-- Absent Days --}}
                            <td class="text-center">{{ $slip->absent_days }}</td>
                            {{-- Late & Half Days --}}
                            <td class="text-center">
                                {{ $slip->late_days }}
                                @if($slip->half_days > 0)
                                    <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">{{ $slip->half_days }} half</span>
                                @endif
                            </td>
                            {{-- Overtime Hours --}}
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $slip->overtime_hours, 2), '0'), '.') }}</td>
                            {{-- Salary Calculations --}}
                            <td class="text-end">৳ {{ number_format((float) $slip->gross_salary, 2) }}</td>
                            <td class="text-end text-danger">৳ {{ number_format((float) $slip->total_deductions, 2) }}</td>
                            <td class="text-end fw-semibold">৳ {{ number_format((float) $slip->net_salary, 2) }}</td>
                            {{-- Action Buttons (View Payslip HTML & PDF Download) --}}
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.payslip', [$run, $slip]) }}" title="View payslip">
                                    <i class="bi bi-receipt-cutoff"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-success" href="{{ route('payroll.payslip.pdf', [$run, $slip]) }}" title="Download PDF">
                                    <i class="bi bi-filetype-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No payslips. Set salaries and regenerate.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

