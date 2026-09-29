@csrf

<div class="mb-3">
    <label class="form-label">Full name</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person"></i></span>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user->name ?? '') }}" placeholder="e.g. Jane Doe" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label">Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email ?? '') }}" placeholder="you@example.com" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-5">
        <label class="form-label">Role</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-shield"></i></span>
            <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" required>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role ?? \App\Models\User::ROLE_USER) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="mb-3 mt-3">
    <label class="form-label">Linked employee <span class="text-muted">(for self-service portal)</span></label>
    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
        <option value="">— None —</option>
        @foreach($employees ?? [] as $emp)
            <option value="{{ $emp->id }}" @selected((string) old('employee_id', $user->employee_id ?? '') === (string) $emp->id)>
                {{ $emp->name }} ({{ $emp->employee_code }})
            </option>
        @endforeach
        @isset($user)
            @if($user->employee && ! collect($employees ?? [])->contains('id', $user->employee_id))
                <option value="{{ $user->employee_id }}" selected>
                    {{ $user->employee->name }} ({{ $user->employee->employee_code }})
                </option>
            @endif
        @endisset
    </select>
    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">Linking lets this user open My attendance, leave and payslips.</div>
</div>

<div class="card bg-body-tertiary border mt-3" id="roleInfo">
    <div class="card-body py-2">
        <div class="small text-muted mb-2" id="roleDesc"></div>
        <div class="d-flex flex-wrap gap-1" id="rolePerms"></div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var descriptions = @json($roleDescriptions ?? []);
        var perms = @json($rolePermissions ?? []);
        var sel = document.getElementById('roleSelect');
        var descEl = document.getElementById('roleDesc');
        var permsEl = document.getElementById('rolePerms');
        if (!sel) return;

        function render() {
            var role = sel.value;
            descEl.textContent = descriptions[role] || '';
            permsEl.innerHTML = '';
            (perms[role] || []).forEach(function (p) {
                var span = document.createElement('span');
                span.className = 'badge rounded-pill text-bg-secondary-subtle text-secondary border fw-normal';
                span.innerHTML = '<i class="bi bi-check2 me-1"></i>' + p;
                permsEl.appendChild(span);
            });
        }

        sel.addEventListener('change', render);
        render();
    })();
</script>
@endpush

<hr class="my-4">

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Password @isset($user)<span class="text-muted">(leave blank to keep)</span>@endisset</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" @unless(isset($user)) required @endunless>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="password_confirmation" class="form-control"
                   placeholder="••••••••" @unless(isset($user)) required @endunless>
        </div>
    </div>
</div>
