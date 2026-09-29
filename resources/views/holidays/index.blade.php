@extends('layouts.app')

@section('title', 'Holidays')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-calendar-event me-2"></i>Holidays</h1>
            <div class="text-muted hide-xs-desc">Company holidays are excluded from working days and absences in payroll.</div>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('leave.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Add holiday</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('holidays.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" maxlength="100" value="{{ old('name') }}" required placeholder="e.g. Independence Day">
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-lg me-1"></i>Add holiday</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar3 me-1"></i>Holidays</span>
                    <form method="GET" action="{{ route('holidays.index') }}">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($years as $y)
                                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Name</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td class="fw-semibold">{{ $holiday->date->format('M j, Y') }}</td>
                                    <td class="text-muted">{{ $holiday->date->format('l') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('holidays.destroy', $holiday) }}" class="d-inline"
                                              onsubmit="return confirm('Remove this holiday?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                        No holidays for {{ $year }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
