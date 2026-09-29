@csrf

@php
    $selectedWeekend = collect(old('weekend_days', isset($employee) ? $employee->weekendDays() : \App\Models\Employee::DEFAULT_WEEKEND_DAYS))
        ->map(fn ($d) => (int) $d)->all();
    $weekdayNames = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Full name</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $employee->name ?? '') }}" placeholder="e.g. John Doe" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @php $curStatus = old('status', $employee->status ?? 'active'); @endphp
                <option value="active" @selected($curStatus === 'active')>Active</option>
                <option value="inactive" @selected($curStatus === 'inactive')>Inactive</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-4">
        <label class="form-label">Employee code (device PIN)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
            <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                   value="{{ old('employee_code', $employee->employee_code ?? '') }}" placeholder="e.g. 1001" required>
            @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Unique employee identification code for attendance tracking.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Department <span class="text-muted">(optional)</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-building"></i></span>
            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                <option value="">— none —</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $employee->department_id ?? '') === (string) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Designation <span class="text-muted">(optional)</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
            <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror">
                <option value="">— none —</option>
                @foreach($designations as $designation)
                    <option value="{{ $designation->id }}" @selected((string) old('designation_id', $employee->designation_id ?? '') === (string) $designation->id)>
                        {{ $designation->name }}
                    </option>
                @endforeach
            </select>
            @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-4">
        <label class="form-label">Email <span class="text-muted">(optional)</span></label>
        <div class="input-group has-validation">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $employee->email ?? '') }}" placeholder="name@example.com">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
        <div class="input-group has-validation">
            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $employee->phone ?? '') }}" placeholder="e.g. 01700000000">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Joining date <span class="text-muted">(optional)</span></label>
        <div class="input-group has-validation">
            <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
            <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror"
                   @if(!isset($employee)) min="{{ date('Y-m-d') }}" @endif
                   value="{{ old('joining_date', isset($employee) && $employee->joining_date ? $employee->joining_date->format('Y-m-d') : '') }}">
            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mt-3">
    <label class="form-label d-block">Weekly off <span class="text-muted">(weekend)</span></label>
    <div class="d-flex flex-wrap gap-3 border rounded-3 p-2">
        @foreach($weekdayNames as $num => $name)
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="weekend_days[]" value="{{ $num }}"
                       id="weekend_{{ $num }}" @checked(in_array($num, $selectedWeekend, true))>
                <label class="form-check-label" for="weekend_{{ $num }}">{{ $name }}</label>
            </div>
        @endforeach
    </div>
    <div class="form-text">Weekend days are excluded from absences, leave day counts, and paid leave in payroll. Leave all unchecked if the employee works every day.</div>
    @error('weekend_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<div class="mt-3">
    <label class="form-label">Address <span class="text-muted">(optional)</span></label>
    <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror"
              placeholder="Street, city…">{{ old('address', $employee->address ?? '') }}</textarea>
    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

@php
    $linkedUser = isset($employee) ? $employee->user : null;
    $staffLocked = $linkedUser && ! $linkedUser->isPortalOnly();
    $portalEnabledDefault = $linkedUser !== null;
    $portalEnabled = (bool) old('enable_portal_login', $portalEnabledDefault);
    $portalEmailDefault = $linkedUser?->email ?? (isset($employee) ? ($employee->email ?? '') : '');
@endphp

<hr class="my-4">

<div class="card border bg-body-tertiary">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-2">
            <div>
                <h2 class="h6 mb-1"><i class="bi bi-person-badge me-1"></i>Portal login</h2>
                <p class="small text-muted mb-0">Lets this employee sign in at the same login page to view attendance, request leave, and download payslips.</p>
            </div>
            @if($staffLocked)
                <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">Staff account linked</span>
            @elseif($linkedUser)
                <span class="badge text-bg-success-subtle text-success border border-success-subtle">Portal enabled</span>
            @endif
        </div>

        @if($staffLocked)
            <div class="alert alert-warning mb-0 py-2 small">
                Linked to <strong>{{ $linkedUser->email }}</strong> as <strong>{{ $linkedUser->roleLabel() }}</strong>.
                Manage this account under
                <a href="{{ route('users.edit', $linkedUser) }}">Users &amp; Roles</a>
                — portal login cannot be changed from this form.
            </div>
        @else
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="enable_portal_login" value="1"
                       id="enablePortalLogin" @checked($portalEnabled)>
                <label class="form-check-label" for="enablePortalLogin">Enable employee portal login</label>
            </div>

            <div id="portalLoginFields" @unless($portalEnabled) class="d-none" @endunless>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Login email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="portal_email" class="form-control @error('portal_email') is-invalid @enderror"
                                   value="{{ old('portal_email', $portalEmailDefault) }}" placeholder="employee@example.com">
                            @error('portal_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Used to sign in. Defaults to the employee email when set.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password @if($linkedUser)<span class="text-muted">(leave blank to keep)</span>@endif</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="portal_password" class="form-control @error('portal_password') is-invalid @enderror"
                                   placeholder="••••••••" autocomplete="new-password">
                            @error('portal_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="portal_password_confirmation" class="form-control"
                                   placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                @if($linkedUser)
                    <div class="form-text mt-2">Uncheck “Enable” and save to remove this portal account.</div>
                @endif
            </div>
        @endif
    </div>
</div>

@unless($staffLocked)
@push('scripts')
<script>
    (function () {
        var toggle = document.getElementById('enablePortalLogin');
        var fields = document.getElementById('portalLoginFields');
        if (!toggle || !fields) return;
        function sync() {
            fields.classList.toggle('d-none', !toggle.checked);
        }
        toggle.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
@endunless
