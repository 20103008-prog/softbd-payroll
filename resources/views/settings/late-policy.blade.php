@extends('layouts.app')

@section('title', 'Late Policy')

@section('content')
    {{-- ========================================================================= --}}
    {{-- 1. PAGE TOOLBAR & HEADER SECTION --}}
    {{-- ========================================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-alarm me-2"></i>Late Policy</h1>
            <div class="text-muted hide-xs-desc">Define the expected start time and grace period used to flag late arrivals in reports.</div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. FORM VALIDATION ERROR ALERTS SECTION --}}
    {{-- ========================================================================= --}}
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div>Please fix the errors below.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            {{-- ========================================================================= --}}
            {{-- 3. LATE POLICY CONFIGURATION FORM CARD --}}
            {{-- ========================================================================= --}}
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.late-policy.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            {{-- Field 1: Official Work Start Time (e.g., 09:00 AM) --}}
                            <div class="col-md-6">
                                <label class="form-label">Work start time</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                    <input type="time" name="work_start_time" class="form-control @error('work_start_time') is-invalid @enderror"
                                           value="{{ old('work_start_time', $policy->startTimeForInput()) }}" required>
                                    @error('work_start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-text">The official daily start time (e.g. 09:00).</div>
                            </div>
                            {{-- Field 2: Allowed Grace Period in Minutes (e.g., 10 mins) --}}
                            <div class="col-md-6">
                                <label class="form-label">Grace period (minutes)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hourglass-split"></i></span>
                                    <input type="number" name="grace_minutes" min="0" max="600"
                                           class="form-control @error('grace_minutes') is-invalid @enderror"
                                           value="{{ old('grace_minutes', $policy->grace_minutes) }}" required>
                                    @error('grace_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-text">Arrivals within this many minutes after start are still on-time.</div>
                            </div>
                        </div>

                        {{-- Field 3: Half-Day Cutoff Threshold in Minutes (Optional) --}}
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Half-day after (minutes) <span class="text-muted">(optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-pie-chart"></i></span>
                                    <input type="number" name="half_day_after_minutes" min="1" max="1440"
                                           class="form-control @error('half_day_after_minutes') is-invalid @enderror"
                                           value="{{ old('half_day_after_minutes', $policy->half_day_after_minutes) }}"
                                           placeholder="e.g. 120">
                                    @error('half_day_after_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-text">If someone is late by more than this, mark the day as half-day. Leave blank to disable.</div>
                            </div>
                        </div>

                        {{-- Form Action Buttons: Submit & Link to Attendance Reports --}}
                        <div class="mt-4 d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save policy</button>
                            <a class="btn btn-outline-secondary" href="{{ route('reports.attendance') }}">View reports</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 4. INFORMATION SIDE CARD: HOW LATE POLICY CALCULATION WORKS --}}
        {{-- ========================================================================= --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i>How it works</div>
                <div class="card-body">
                    <p class="mb-2">A day's <strong>check-in</strong> (first punch) is compared to:</p>
                    <p class="mb-3">
                        <span class="badge text-bg-light border">Start</span>
                        <i class="bi bi-plus"></i>
                        <span class="badge text-bg-light border">Grace</span>
                        <i class="bi bi-arrow-right"></i>
                        <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle">Late cutoff</span>
                    </p>
                    <ul class="mb-0 small">
                        <li>Check-in <strong>at or before</strong> the cutoff → <span class="text-success fw-semibold">On time</span>.</li>
                        <li>Check-in <strong>after</strong> the cutoff → <span class="text-danger fw-semibold">Late</span>, with minutes shown.</li>
                        <li>Example: start 09:00, grace 10 → anyone after 09:10 is late.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

