@extends('layouts.app')

@section('title', 'Roster')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR HEADER SECTION --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: #1e3a8a; -webkit-text-stroke: 1px #0f172a;">
                <i class="bi bi-calendar2-range me-2"></i>Roster / Duty
            </h1>
            <div class="text-muted hide-xs-desc" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold; color: #475569;">
                Define shifts and assign them to employees by date range. Late detection uses the assigned shift when available.
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. VALIDATION ERROR ALERTS SECTION --}}
    {{-- ========================================================= --}}
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-x-circle-fill mt-1"></i>
            <div>
                <div class="fw-semibold">Please fix the errors below.</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        {{-- ========================================================= --}}
        {{-- 3. LEFT COLUMN: SHIFTS DEFINITION TABLE & MODAL TRIGGER --}}
        {{-- ========================================================= --}}
        <div class="col-lg-5">
            <div class="card h-100" style="background-color: #eff6ff; font-weight: bold;">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #1e40af; color: white; font-family: Arial, sans-serif; font-weight: bold; font-size: 18px;">
                    <span><i class="bi bi-clock me-1"></i>Shifts</span>
                    {{-- Action Button: Add Shift Modal Trigger --}}
                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="modal" data-bs-target="#shiftModal"
                            onclick="openShiftForm()" style="font-weight: bold;">
                        <i class="bi bi-plus-lg me-1"></i>Add shift
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="background-color: #3b82f6; color: white; font-family: Arial, sans-serif; font-weight: bold;">Name</th>
                                <th style="background-color: #3b82f6; color: white; font-family: Arial, sans-serif; font-weight: bold;">Hours</th>
                                <th class="text-center" style="background-color: #3b82f6; color: white; font-family: Arial, sans-serif; font-weight: bold;">Grace</th>
                                <th class="text-end" style="background-color: #3b82f6; color: white; font-family: Arial, sans-serif; font-weight: bold;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shifts as $shift)
                                <tr>
                                    <td style="background-color: #eff6ff;">
                                        <div class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold; color: #1e3a8a;">{{ $shift->name }}</div>
                                        <div class="small text-muted">
                                            {{ $shift->assignments_count }} active assignment{{ $shift->assignments_count === 1 ? '' : 's' }}
                                            @if($shift->is_overnight)
                                                <span class="badge text-bg-secondary-subtle text-secondary border ms-1">overnight</span>
                                            @endif
                                        </div>
                                    </td>
                                    {{-- ========================================================= --}}
                                    {{-- CUSTOM EDIT: Display shift time in 12-hour AM/PM format --}}
                                    {{-- ========================================================= --}}
                                    <td class="text-nowrap" style="background-color: #eff6ff; font-weight: bold;">{{ $shift->formattedStartTime() }} – {{ $shift->formattedEndTime() }}</td>
                                    <td class="text-center" style="background-color: #eff6ff; font-weight: bold;">{{ $shift->grace_minutes }}m</td>
                                    {{-- Row Action Buttons (Edit Shift, Delete Shift) --}}
                                    <td class="text-end text-nowrap" style="background-color: #c9d7ebff;">
                                        <button style="background-color: #88aedfff; font-weight: bold;"
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary btn-edit-shift"
                                            title="Edit"
                                            data-id="{{ $shift->id }}"
                                            data-name="{{ $shift->name }}"
                                            data-start="{{ $shift->startTimeForInput() }}"
                                            data-end="{{ $shift->endTimeForInput() }}"
                                            data-grace="{{ $shift->grace_minutes }}"
                                            data-break-minutes="{{ $shift->break_minutes }}"
                                            data-ot="{{ $shift->overtime_starts_after_minutes }}"
                                            data-overnight="{{ $shift->is_overnight ? '1' : '0' }}"
                                            data-url="{{ route('roster.shifts.update', $shift) }}"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('roster.shifts.destroy', $shift) }}" class="d-inline"
                                              onsubmit="return confirm('Delete shift {{ $shift->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4" style="background-color: #eff6ff;">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No shifts yet. Add Morning / Night / etc.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- 4. RIGHT COLUMN: SHIFT ASSIGNMENT FORM CARD --}}
        {{-- ========================================================= --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header" style="background-color: #115e59; color: white; font-family: Arial, sans-serif; font-weight: bold; font-size: 18px;">
                    <i class="bi bi-person-badge me-1"></i>Assign shift
                </div>
                <div class="card-body" style="background-color: #f0fdfa;">
                    @if($shifts->isEmpty() || $employees->isEmpty())
                        <div class="alert alert-warning mb-0">
                            @if($employees->isEmpty())
                                Add <a href="{{ route('employees.index') }}" class="alert-link">employees</a> first.
                            @endif
                            @if($shifts->isEmpty())
                                Create at least one shift before assigning.
                            @endif
                        </div>
                    @else
                        {{-- Form: Assign Shift to Employee --}}
                        <form method="POST" action="{{ route('roster.assignments.store') }}" class="row g-3">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #134e4a;">Employee</label>
                                <select name="employee_id" class="form-select" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #0f766e;">
                                    <option value="">Select…</option>
                                    @php
                                        $authUser = auth()->user();
                                        $authRole = $authUser?->role;
                                        $authEmpId = $authUser?->employee_id;
                                    @endphp
                                    @foreach($employees as $employee)
                                        @php
                                            $isSelf = ($authEmpId && $employee->id == $authEmpId);
                                            $targetRole = $employee->user?->role;
                                            $isRestricted = false;

                                            // 1. Cannot assign shift to oneself
                                            if ($isSelf) {
                                                $isRestricted = true;
                                            }

                                            // 2. Manager cannot assign shift to Admin, Super Admin, or HR Manager
                                            if ($authRole === \App\Models\User::ROLE_MANAGER && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_HR], true)) {
                                                $isRestricted = true;
                                            }

                                            // 3. HR Manager cannot assign shift to Admin or Super Admin
                                            if ($authRole === \App\Models\User::ROLE_HR && in_array($targetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
                                                $isRestricted = true;
                                            }
                                        @endphp

                                        @if(! $isRestricted)
                                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                                {{ $employee->name }} ({{ $employee->employee_code }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #134e4a;">Shift</label>
                                <select name="shift_id" class="form-select" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #0f766e;">
                                    <option value="">Select…</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>
                                            {{ $shift->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- ========================================================= --}}
                            {{-- CUSTOM EDIT: Restrict past dates using min="{{ date('Y-m-d') }}" --}}
                            {{-- ========================================================= --}}
                            <div class="col-md-5">
                                <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #134e4a;">From</label>
                                <input type="date" name="effective_from" class="form-control"
                                       value="{{ old('effective_from', now()->toDateString()) }}" min="{{ date('Y-m-d') }}" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #0f766e;">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #134e4a;">To <span class="text-muted">(optional)</span></label>
                                <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}" min="{{ date('Y-m-d') }}" style="background-color: #ffffff; font-weight: bold; border: 1px solid #0f766e;">
                                <div class="form-text">Leave blank for ongoing.</div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-teal w-100" type="submit" style="background-color: #0f766e; color: white; font-weight: bold;"><i class="bi bi-check-lg"></i></button>
                            </div>

                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold; font-size: 18px;">
            <i class="bi bi-list-ul me-1"></i>Assignments
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="background-color: #475569; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th>
                        <th style="background-color: #475569; color: white; font-family: Arial, sans-serif; font-weight: bold;">Shift</th>
                        <th style="background-color: #475569; color: white; font-family: Arial, sans-serif; font-weight: bold;">Period</th>
                        <th class="text-end" style="background-color: #475569; color: white; font-family: Arial, sans-serif; font-weight: bold;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td style="background-color: #f8fafc;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="background-color: #cbd5e1; color: #0f172a; font-weight: bold;">{{ strtoupper(mb_substr(optional($a->employee)->name ?? '?', 0, 1)) }}</span>
                                    <div>
                                        <div class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold;">{{ optional($a->employee)->name ?? '—' }}</div>
                                        <div class="small text-muted">Code: {{ optional($a->employee)->employee_code ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="background-color: #f8fafc;">
                                @if($a->shift)
                                    <div class="fw-semibold" style="font-weight: bold;">{{ $a->shift->name }}</div>
                                    {{-- ========================================================= --}}
                                    {{-- CUSTOM EDIT: Display assignment shift time in 12-hour AM/PM --}}
                                    {{-- ========================================================= --}}
                                    <div class="small text-muted">{{ $a->shift->formattedStartTime() }} – {{ $a->shift->formattedEndTime() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="background-color: #f8fafc; font-weight: bold;">{{ $a->periodLabel() }}</td>
                            <td class="text-end" style="background-color: #f8fafc;">
                                @php
                                    $aEmpId = $a->employee?->id;
                                    $aIsSelf = ($authEmpId && $aEmpId == $authEmpId);
                                    $aTargetRole = $a->employee?->user?->role;
                                    $aRestricted = false;

                                    if ($aIsSelf) {
                                        $aRestricted = true;
                                    }
                                    if ($authRole === \App\Models\User::ROLE_MANAGER && in_array($aTargetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_HR], true)) {
                                        $aRestricted = true;
                                    }
                                    if ($authRole === \App\Models\User::ROLE_HR && in_array($aTargetRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
                                        $aRestricted = true;
                                    }
                                @endphp

                                @if(! $aRestricted)
                                    <form method="POST" action="{{ route('roster.assignments.destroy', $a) }}" class="d-inline"
                                          onsubmit="return confirm('Remove this assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary opacity-50" disabled title="You cannot modify this shift assignment">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4" style="background-color: #f8fafc;">
                                No shift assignments yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assignments->hasPages())
            <div class="card-body">{{ $assignments->links() }}</div>
        @endif
    </div>


    {{-- Create / edit shift modal --}}
    <div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="shiftForm" action="{{ route('roster.shifts.store') }}" class="modal-content">
                @csrf
                <div id="shiftMethodSlot"></div>
                <div class="modal-header">
                    <h5 class="modal-title" id="shiftModalTitle">Add shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="shift_name" class="form-control" placeholder="e.g. Morning" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Start</label>
                            <input type="time" name="start_time" id="shift_start" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End</label>
                            <input type="time" name="end_time" id="shift_end" class="form-control" value="17:00" required>
                        </div>
                    <!-- Real-time 12-Hour AM/PM Shift Timing Preview Box -->
                    <div class="col-12 mt-2">
                        <div class="p-2 border rounded bg-primary-subtle text-primary fw-bold small">
                            <i class="bi bi-clock me-1"></i> Shift Timing Preview: <span id="shift_time_preview">9:00 AM – 5:00 PM</span>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-6">
                        <label class="form-label">Grace (minutes)</label>
                        <input type="number" name="grace_minutes" id="shift_grace" class="form-control" value="0" min="0" max="600">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Break (minutes)</label>
                        <input type="number" name="break_minutes" id="shift_break" class="form-control" value="0" min="0" max="480">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">OT starts after (minutes past shift start)</label>
                    <input type="number" name="overtime_starts_after_minutes" id="shift_ot" class="form-control" value="0" min="0" max="1440">
                    <div class="form-text">0 = use expected shift length for overtime elsewhere.</div>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="is_overnight" value="1" id="shift_overnight">
                    <label class="form-check-label" for="shift_overnight">Overnight shift (ends next day)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="shiftSubmit">Save shift</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /* ========================================================================= */
    /* CUSTOM EDIT: 12-HOUR AM/PM SHIFT TIMING PREVIEW & MODAL LOGIC */
    /* ========================================================================= */

    // Base URL for storing new shifts via POST request
    const shiftStoreUrl = @json(route('roster.shifts.store'));

    // Convert 24-hour time string (e.g. "17:00") into 12-hour AM/PM format (e.g. "5:00 PM")
    function formatTimeToAMPM(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hrs = parseInt(h, 10);
        if (isNaN(hrs)) return t;
        return `${hrs % 12 || 12}:${m || '00'} ${hrs >= 12 ? 'PM' : 'AM'}`;
    }

    // Live update the AM/PM preview badge inside the Add/Edit Shift modal
    function updateShiftPreview() {
        const s = document.getElementById('shift_start')?.value;
        const e = document.getElementById('shift_end')?.value;
        const p = document.getElementById('shift_time_preview');
        if (p) p.textContent = `${formatTimeToAMPM(s)} – ${formatTimeToAMPM(e)}`;
    }

    // Attach input event listeners to update AM/PM preview on time selection
    ['shift_start', 'shift_end'].forEach(id => document.getElementById(id)?.addEventListener('input', updateShiftPreview));

    // Open modal form for adding a new shift or editing an existing shift
    function openShiftForm(data = null) {
        const form = document.getElementById('shiftForm');
        document.getElementById('shiftMethodSlot').innerHTML = data ? '<input type="hidden" name="_method" value="PUT">' : '';
        document.getElementById('shiftModalTitle').textContent = data ? 'Edit shift' : 'Add shift';
        form.action = data ? data.url : shiftStoreUrl;

        const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
        setVal('shift_name', data?.name || '');
        setVal('shift_start', data?.start || '09:00');
        setVal('shift_end', data?.end || '17:00');
        setVal('shift_grace', data?.grace || 0);
        setVal('shift_break', data?.breakMinutes || 0);
        setVal('shift_ot', data?.ot || 0);

        const ov = document.getElementById('shift_overnight');
        if (ov) ov.checked = Boolean(data?.overnight == '1' || data?.overnight === true);

        // Update live AM/PM preview & display Bootstrap modal
        updateShiftPreview();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('shiftModal')).show();
    }

    // Attach click event listeners for shift Edit buttons in the list table
    document.querySelectorAll('.btn-edit-shift').forEach(btn => {
        btn.addEventListener('click', () => openShiftForm({ ...btn.dataset }));
    });
</script>
@endpush
