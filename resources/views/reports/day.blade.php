@extends('layouts.app')

@section('title', 'Edit attendance day')

@php
    $name = $employee->name ?? ('#'.$code);
    $checkIn = $punches->min('punched_at');
    $checkOut = $punches->count() > 1 ? $punches->max('punched_at') : null;
    $statuses = ['in', 'out'];
@endphp

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <a href="{{ route('reports.attendance', ['from' => $date, 'to' => $date]) }}" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i>Back to reports
            </a>
            <h1 class="h4 mb-1 mt-2"><i class="bi bi-pencil-square me-2"></i>Edit attendance</h1>
            <div class="text-muted">
                <span class="fw-semibold">{{ $name }}</span> (code {{ $code }})
                <span class="d-none d-sm-inline">&middot; {{ \Illuminate\Support\Carbon::parse($date)->format('l, M d, Y') }}</span>
                <div class="d-sm-none small">{{ \Illuminate\Support\Carbon::parse($date)->format('D, M d, Y') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label mb-2">Check-in</div>
                    <div class="stat-value text-success">{{ $checkIn ? \Illuminate\Support\Carbon::parse($checkIn)->format('H:i') : '—' }}</div>
                    <i class="bi bi-box-arrow-in-right stat-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label mb-2">Check-out</div>
                    <div class="stat-value text-danger">{{ $checkOut ? \Illuminate\Support\Carbon::parse($checkOut)->format('H:i') : '—' }}</div>
                    <i class="bi bi-box-arrow-right stat-icon text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label mb-2">Punches</div>
                    <div class="stat-value text-primary">{{ $punches->count() }}</div>
                    <i class="bi bi-fingerprint stat-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Forms live outside the table (invalid to nest <form> in <tr>); cells link via the form="" attribute. --}}
    @foreach($punches as $p)
        <form id="update-{{ $p->id }}" method="POST" action="{{ route('reports.punches.update', $p) }}" class="d-none">
            @csrf
            @method('PUT')
        </form>
        <form id="delete-{{ $p->id }}" method="POST" action="{{ route('reports.punches.destroy', $p) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-list-ul me-1"></i>Punches this day</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width: 40%">Time</th>
                        <th style="width: 25%">Status</th>
                        <th>Source</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($punches as $p)
                        <tr>
                            <td>
                                <input type="datetime-local" form="update-{{ $p->id }}" name="punched_at"
                                       class="form-control form-control-sm"
                                       value="{{ $p->punched_at->format('Y-m-d\TH:i') }}" required>
                            </td>
                            <td>
                                <select name="status" form="update-{{ $p->id }}" class="form-select form-select-sm">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}" @selected($p->status === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                    @unless(in_array($p->status, $statuses, true))
                                        <option value="{{ $p->status }}" selected>{{ $p->status }}</option>
                                    @endunless
                                </select>
                            </td>
                            <td class="text-muted small">{{ optional($p->device)->name ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="submit" form="update-{{ $p->id }}" title="Save changes">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" type="submit" form="delete-{{ $p->id }}" title="Delete"
                                        onclick="return confirm('Delete this punch?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                No punches for this day. Add one below.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Add a punch</div>
        <div class="card-body">
            <form method="POST" action="{{ route('reports.punches.store') }}" class="row g-3 align-items-end">
                @csrf
                <input type="hidden" name="employee_code" value="{{ $code }}">
                <div class="col-md-5">
                    <label class="form-label">Time</label>
                    <input type="datetime-local" name="punched_at" class="form-control"
                           value="{{ $date }}T09:00" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach($statuses as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-success w-100" type="submit">
                        <i class="bi bi-plus-lg me-1"></i>Add punch
                    </button>
                </div>
            </form>
            <p class="text-muted small mb-0 mt-2">Manual punches are tagged to a “Manual Entry” source so you can tell them apart from device data.</p>
        </div>
    </div>
@endsection
