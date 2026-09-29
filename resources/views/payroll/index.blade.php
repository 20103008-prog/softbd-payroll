@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & NAVIGATION ACTION BUTTONS --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2"></i>Payroll</h1>
            <div class="text-muted hide-xs-desc">Generate monthly payroll from attendance and manage payslips.</div>
        </div>
        <div class="page-actions">
            {{-- Employee Salaries Setup Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('payroll.salaries') }}">
                <i class="bi bi-wallet2 me-1"></i><span class="d-none d-sm-inline">Employee salaries</span><span class="d-sm-none">Salaries</span>
            </a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. VALIDATION ERROR ALERTS SECTION --}}
    {{-- ========================================================= --}}
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div>
                Please fix the errors below.
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row g-3">
        {{-- ========================================================= --}}
        {{-- 3. LEFT COLUMN: GENERATE PAYROLL FORM CARD --}}
        {{-- ========================================================= --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-play-circle me-1"></i>Generate payroll</div>
                <div class="card-body">
                    {{-- Warning alert if no salaries set --}}
                    @if($salariedCount === 0)
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>No salaries set yet. <a href="{{ route('payroll.salaries') }}" class="alert-link">Set employee salaries</a> first.</div>
                        </div>
                    @endif

                    {{-- Form: Generate Payroll --}}
                    <form method="POST" action="{{ route('payroll.store') }}">
                        @csrf
                        {{-- Month Selector --}}
                        <div class="mb-3">
                            <label class="form-label">Month</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-month"></i></span>
                                <input type="month" name="period" class="form-control @error('period') is-invalid @enderror"
                                       value="{{ old('period', $defaultMonth) }}" required>
                                @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Re-generating overwrites payslips unless the month is locked.</div>
                        </div>

                        {{-- Working Days & Daily Hours Grid --}}
                        <div class="row g-2 g-md-3">
                            <div class="col-6">
                                <label class="form-label">Working days</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                    <input type="number" name="working_days" min="1" max="31"
                                           class="form-control @error('working_days') is-invalid @enderror"
                                           value="{{ old('working_days', $defaultWorkingDays) }}">
                                    @error('working_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-text hide-xs-desc">Defaults to working days (Fri–Sat weekend, holidays excluded).</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Std. hours / day</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-clock-history"></i></span>
                                    <input type="number" name="standard_daily_hours" min="1" max="24" step="0.5"
                                           class="form-control @error('standard_daily_hours') is-invalid @enderror"
                                           value="{{ old('standard_daily_hours', 8) }}" required>
                                    @error('standard_daily_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-text hide-xs-desc">Hours beyond this count as overtime.</div>
                            </div>
                        </div>

                        {{-- Submit Button: Generate Payroll Engine --}}
                        <button class="btn btn-primary mt-4 w-100" type="submit">
                            <i class="bi bi-gear-wide-connected me-1"></i>Generate payroll
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- 4. RIGHT COLUMN: PAYROLL RUNS HISTORY TABLE CARD --}}
        {{-- ========================================================= --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-clock-history me-1"></i>Payroll runs</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Status</th>
                                <th class="text-center">Payslips</th>
                                <th class="text-end">Total net</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                                <tr>
                                    {{-- Month Period Label --}}
                                    <td class="fw-semibold">{{ $run->periodLabel() }}</td>
                                    {{-- Status Badge (Draft / Generated / Locked) --}}
                                    <td>
                                        @if($run->status === 'locked')
                                            <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">Locked</span>
                                        @elseif($run->status === 'generated')
                                            <span class="badge text-bg-success-subtle text-success border border-success-subtle">Generated</span>
                                        @else
                                            <span class="badge text-bg-secondary-subtle text-secondary border">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $run->payslips_count }}</td>
                                    <td class="text-end">৳ {{ number_format((float) ($run->payslips_sum_net_salary ?? 0), 2) }}</td>
                                    {{-- Row Action Buttons (View Sheet, Delete Run) --}}
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.show', $run) }}" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @unless($run->status === 'locked')
                                            <form method="POST" action="{{ route('payroll.destroy', $run) }}" class="d-inline"
                                                  onsubmit="return confirm('Delete payroll for {{ $run->periodLabel() }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No payroll generated yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Pagination Links --}}
                @if($runs->hasPages())
                    <div class="card-body">{{ $runs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

