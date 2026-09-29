@extends('layouts.app')

@section('title', 'Salary Components — '.$employee->name)

@php
    $basic = (float) ($employee->salaryStructure->monthly_salary ?? 0);
    $authUser = auth()->user();
    $authRole = $authUser?->role;
    $authEmpId = $authUser?->employee_id;
    $targetRole = $employee->user?->role;

    $isRestricted = false;
    if ($authEmpId && $employee->id == $authEmpId) {
        $isRestricted = true;
    }
    if ($authRole === \App\Models\User::ROLE_HR && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
        $isRestricted = true;
    }
    if ($authRole === \App\Models\User::ROLE_MANAGER && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_HR], true)) {
        $isRestricted = true;
    }
@endphp

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-sliders me-2"></i>Salary Components</h1>
            <div class="text-muted">
                {{ $employee->name }} <span class="text-muted">(code {{ $employee->employee_code }})</span>
                <span class="hide-xs-desc">&middot; Basic: <strong>৳ {{ number_format($basic, 2) }}</strong></span>
            </div>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('payroll.salaries') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    @if($basic <= 0)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>This employee has no basic salary set. <a href="{{ route('payroll.salaries') }}" class="alert-link">Set the basic salary</a> so percentage components can be calculated.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Add component</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payroll.components.store', $employee) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" maxlength="100" required placeholder="e.g. House Rent, PF, Tax">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="earning">Earning (allowance / bonus)</option>
                                <option value="deduction">Deduction (tax / PF / other)</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Calc</label>
                                <select name="calc_type" class="form-select" required>
                                    <option value="fixed">Fixed ৳</option>
                                    <option value="percent">% of basic</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Value</label>
                                <input type="number" name="amount" step="0.01" min="0" class="form-control" value="0" required>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 mt-3" type="submit" @disabled($isRestricted)><i class="bi bi-check-lg me-1"></i>Add</button>
                    </form>
                    <div class="form-text mt-2">Percentages are calculated on the <strong>basic</strong> salary.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul me-1"></i>Components</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Name</th>
                                <th style="width: 18%;">Type</th>
                                <th style="width: 16%;">Calc</th>
                                <th style="width: 16%;">Value</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->salaryComponents as $component)
                                <tr>
                                    <td>
                                        <input type="text" name="name" form="sc-{{ $component->id }}" class="form-control form-control-sm"
                                               value="{{ $component->name }}" required @disabled($isRestricted)>
                                    </td>
                                    <td>
                                        <select name="type" form="sc-{{ $component->id }}" class="form-select form-select-sm" @disabled($isRestricted)>
                                            <option value="earning" @selected($component->type === 'earning')>Earning</option>
                                            <option value="deduction" @selected($component->type === 'deduction')>Deduction</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="calc_type" form="sc-{{ $component->id }}" class="form-select form-select-sm" @disabled($isRestricted)>
                                            <option value="fixed" @selected($component->calc_type === 'fixed')>Fixed ৳</option>
                                            <option value="percent" @selected($component->calc_type === 'percent')>% basic</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="amount" form="sc-{{ $component->id }}" step="0.01" min="0"
                                               class="form-control form-control-sm" value="{{ $component->amount }}" required @disabled($isRestricted)>
                                    </td>
                                    <td class="text-end {{ $component->type === 'earning' ? 'text-success' : 'text-danger' }}">
                                        {{ $component->type === 'earning' ? '+' : '−' }}$ {{ number_format($component->resolve($basic), 2) }}
                                    </td>
                                    <td class="text-end">
                                        @if(! $isRestricted)
                                            <button class="btn btn-sm btn-primary" type="submit" form="sc-{{ $component->id }}" title="Save"><i class="bi bi-check-lg"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" type="submit" form="sc-del-{{ $component->id }}"
                                                    title="Delete" onclick="return confirm('Delete {{ $component->name }}?');"><i class="bi bi-trash"></i></button>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary opacity-50" disabled title="Locked"><i class="bi bi-lock-fill"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-sliders fs-3 d-block mb-2"></i>
                                        No components yet. Add allowances, bonuses, tax, or PF on the left.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Row forms kept outside the table for valid HTML. --}}
    @foreach($employee->salaryComponents as $component)
        <form id="sc-{{ $component->id }}" method="POST" action="{{ route('payroll.components.update', $component) }}" class="d-none">@csrf @method('PUT')</form>
        <form id="sc-del-{{ $component->id }}" method="POST" action="{{ route('payroll.components.destroy', $component) }}" class="d-none">@csrf @method('DELETE')</form>
    @endforeach
@endsection
