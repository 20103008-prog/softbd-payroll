@extends('layouts.app')

@section('title', 'Leave Balances')

@section('content')
{{-- PAGE Background color section start --}}
    <style>
    body {
        background-color: #c2a4aeff !important; /* */
    }
</style>

{{-- PAGE Background color section end --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-calendar2-week me-2"></i>Leave Balances</h1>
            <div class="text-muted hide-xs-desc">Approved leave used vs. annual quota, per employee and type.</div>
        </div>
        <div class="page-actions">
            <form method="GET" action="{{ route('leave.balances') }}">
                <select name="year" class="form-select" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a class="btn btn-outline-secondary" href="{{ route('leave.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-sticky-first">
                <thead>
                    <tr>
                        <th>Employee</th>
                        @foreach($types as $type)
                            <th class="text-center">
                                {{ $type->name }}
                                <div class="fw-normal text-muted" style="font-size:.7rem;">quota {{ $type->annual_quota }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td class="d-flex align-items-center gap-2">
                                <span class="avatar">{{ strtoupper(mb_substr($emp->name, 0, 1)) }}</span>
                                <div>
                                    <a class="fw-semibold text-decoration-none" href="{{ route('employees.show', $emp) }}">{{ $emp->name }}</a>
                                    <div class="small text-muted">Code: {{ $emp->employee_code }}</div>
                                </div>
                            </td>
                            @foreach($types as $type)
                                @php
                                    $u = $used[$emp->id][$type->id] ?? 0;
                                    $rem = $type->annual_quota - $u;
                                @endphp
                                <td class="text-center">
                                    <div><span class="fw-semibold">{{ $u }}</span> <span class="text-muted">/ {{ $type->annual_quota }}</span></div>
                                    <div class="small {{ $rem < 0 ? 'text-danger' : 'text-success' }}">{{ $rem }} left</div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $types->count() + 1 }}" class="text-center text-muted py-4">
                                <i class="bi bi-people fs-3 d-block mb-2"></i>No employees.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="card-body">{{ $employees->links() }}</div>
        @endif
    </div>
@endsection
