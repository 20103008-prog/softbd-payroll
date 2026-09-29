@extends('layouts.app')

@section('title', 'My attendance')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. SELF SERVICE TOOLBAR & CHECK-IN / CHECK-OUT ACTION BUTTON --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-clock-history me-2"></i>My attendance</h1>
            <div class="text-muted">{{ $employee->name }} &middot; Code {{ $employee->employee_code }}</div>
        </div>
        @php
            $checkedIn = $todayLastPunch && $todayLastPunch->status === 'in';
        @endphp
        <div class="page-actions">
            {{-- Action Button: Instant Self-Service Check-In / Check-Out Punch --}}
            <form method="POST" action="{{ route('me.attendance.punch') }}">
                @csrf
                @if($checkedIn)
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-fingerprint me-1"></i>Check Out
                    </button>
                    <div class="small text-muted mt-1 text-end">Inside since {{ $todayLastPunch->punched_at->format('g:i A') }}</div>
                @else
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-fingerprint me-1"></i>Check In
                    </button>
                    <div class="small text-muted mt-1 text-end">Not checked in today</div>
                @endif
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. DATE RANGE FILTER CARD SECTION --}}
    {{-- ========================================================= --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end filter-bar">
                <div class="col-6 col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 3. PERSONAL ATTENDANCE LOGS HISTORY TABLE SECTION --}}
    {{-- ========================================================= --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date / time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($log->punched_at)->format('D, M j, Y g:i A') }}</td>
                            <td><span class="badge text-bg-secondary-subtle text-secondary border">{{ $log->status ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">No punches in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination Links --}}
        @if($logs->hasPages())
            <div class="card-body">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection

