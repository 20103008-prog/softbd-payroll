@extends('layouts.app')

@section('title', 'Add Leave')

@section('content')
    {{-- ========================================================================= --}}
    {{-- 1. PAGE HEADER & BACK NAVIGATION BUTTON --}}
    {{-- ========================================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            {{-- Back to Leave List Button --}}
            <a href="{{ route('leave.index') }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left me-1"></i>Back</a>
            {{-- Page Title & Subtitle --}}
            <h1 class="h4 mb-1"><i class="bi bi-calendar-plus me-2"></i>Add Leave</h1>
            <div class="text-muted hide-xs-desc">Record a leave for an employee.</div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. VALIDATION ERROR ALERT BOX (DISPLAYS FORM VALIDATION ERRORS) --}}
    {{-- ========================================================================= --}}
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
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

    <div class="row">
        <div class="col-lg-7">
            {{-- ========================================================================= --}}
            {{-- 3. MAIN LEAVE APPLICATION FORM CARD --}}
            {{-- ========================================================================= --}}
            <div class="card">
                <div class="card-body">
                    {{-- Warning message if no employees or leave types are available --}}
                    @if($employees->isEmpty() || $leaveTypes->isEmpty())
                        <div class="alert alert-warning d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                @if($employees->isEmpty())You need <a href="{{ route('employees.index') }}" class="alert-link">employees</a> @endif
                                @if($employees->isEmpty() && $leaveTypes->isEmpty()) and @endif
                                @if($leaveTypes->isEmpty())at least one <a href="{{ route('leave-types.index') }}" class="alert-link">leave type</a> @endif
                                before adding leave.
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('leave.store') }}">
                        @csrf

                        {{-- ========================================================================= --}}
                        {{-- 4. EMPLOYEE SELECT DROPDOWN FIELD --}}
                        {{-- ========================================================================= --}}
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                                <option value="">Select employee…</option>
                                @foreach($employees as $employee)
                                    @if(auth()->user()->canManageEmployeeProfile($employee))
                                        <option value="{{ $employee->id }}" @selected((string) old('employee_id', $selectedEmployeeId ?? '') === (string) $employee->id)>
                                            {{ $employee->name }} ({{ $employee->employee_code }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- ========================================================================= --}}
                        {{-- 5. LEAVE TYPE SELECT DROPDOWN FIELD --}}
                        {{-- ========================================================================= --}}
                        <div class="mb-3">
                            <label class="form-label">Leave type</label>
                            <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                                <option value="">Select type…</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                                        {{ $type->name }} @if(! $type->is_paid)(unpaid)@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Helper variable for min date calculation --}}
                        @php
                            $minDate = \Illuminate\Support\Carbon::tomorrow()->toDateString();
                        @endphp

                        {{-- ========================================================================= --}}
                        {{-- 6. LEAVE DATE RANGE INPUT FIELDS (START DATE & END DATE) --}}
                        {{-- ========================================================================= --}}
                        <div class="row g-3">
                            {{-- Start Date Input --}}
                            <div class="col-md-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', $minDate) }}" min="{{ $minDate }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            {{-- End Date Input --}}
                            <div class="col-md-6">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date', $minDate) }}" min="{{ $minDate }}" required>
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        {{-- Working days live calculation preview text --}}
                        <div class="form-text mt-2" id="leaveDaysPreview">
                            <i class="bi bi-info-circle me-1"></i>Weekends &amp; holidays are excluded — only working days count toward the leave balance.
                        </div>

                        {{-- ========================================================================= --}}
                        {{-- 7. REASON FIELD & LEAVE STATUS SELECT DROPDOWN --}}
                        {{-- ========================================================================= --}}
                        <div class="mb-3 mt-3">
                            <label class="form-label">Reason <span class="text-muted">(optional)</span></label>
                            <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Family event">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="approved" @selected(old('status', 'approved') === 'approved')>Approved</option>
                                <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                                <option value="rejected" @selected(old('status') === 'rejected')>Rejected</option>
                            </select>
                            <div class="form-text">Only <strong>Approved</strong> paid leave affects payroll.</div>
                        </div>

                        {{-- ========================================================================= --}}
                        {{-- 8. FORM SUBMIT & CANCEL ACTION BUTTONS --}}
                        {{-- ========================================================================= --}}
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save leave</button>
                            <a class="btn btn-outline-secondary" href="{{ route('leave.index') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 9. ANNUAL LEAVE QUOTA INFORMATION SIDEBAR CARD --}}
        {{-- ========================================================================= --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle me-1"></i>Annual quotas
                </div>
                <div class="card-body">
                    <p class="text-muted small">Approving leave beyond the annual quota is blocked. Types with no quota (e.g. unpaid leave) are unlimited.</p>
                    <ul class="list-group list-group-flush">
                        {{-- List of all leave types and their yearly quotas --}}
                        @foreach($leaveTypes as $type)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ $type->name }} @if(! $type->is_paid)<span class="badge text-bg-light">unpaid</span>@endif</span>
                                <span class="badge text-bg-secondary">
                                    {{ $type->annual_quota > 0 ? $type->annual_quota.' day(s)/yr' : 'Unlimited' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- ========================================================================= --}}
{{-- 10. DYNAMIC WORKING DAYS COUNTER JAVASCRIPT --}}
{{-- ========================================================================= --}}
@push('scripts')
<script>
    (function () {
        var startEl = document.querySelector('input[name="start_date"]');
        var endEl = document.querySelector('input[name="end_date"]');
        var out = document.getElementById('leaveDaysPreview');
        if (!startEl || !endEl || !out) return;

        // Function to calculate weekdays between two dates
        function weekdaysBetween(a, b) {
            var s = new Date(a), e = new Date(b);
            if (isNaN(s) || isNaN(e) || e < s) return null;
            var n = 0;
            for (var d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
                var day = d.getDay(); // 0 Sun, 6 Sat
                if (day !== 0 && day !== 6) n++;
            }
            return n;
        }

        var minDate = "{{ $minDate }}";

        // Function to update end_date min attribute and days preview dynamically
        function update() {
            if (startEl.value && startEl.value > minDate) {
                endEl.min = startEl.value;
                if (endEl.value < startEl.value) {
                    endEl.value = startEl.value;
                }
            } else {
                endEl.min = minDate;
            }

            var n = weekdaysBetween(startEl.value, endEl.value);
            if (n === null) {
                out.innerHTML = '<i class="bi bi-info-circle me-1"></i>Weekends &amp; holidays are excluded — only working days count toward the leave balance.';
                return;
            }
            out.innerHTML = '<i class="bi bi-calendar-check me-1"></i><strong>' + n + '</strong> working day(s) selected (excludes weekends; holidays are also removed when saved).';
        }

        startEl.addEventListener('change', update);
        endEl.addEventListener('change', update);
        update();
    })();
</script>
@endpush
