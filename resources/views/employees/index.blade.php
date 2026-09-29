@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & HEADER ACTION BUTTONS SECTION --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-people me-2"></i>Employees</h1>
            <div class="text-muted hide-xs-desc">Map device codes to real names so reports show who's who.</div>
        </div>
        <div class="page-actions">
            {{-- Navigation Button: Departments & Designations --}}
            <a class="btn btn-outline-secondary" href="{{ route('organization.index') }}">
                <i class="bi bi-diagram-3 me-1"></i><span class="d-none d-md-inline">Departments &amp; Designations</span><span class="d-md-none">Org</span>
            </a>
            {{-- Primary Action Button: Add New Employee --}}
            <a class="btn btn-primary" href="{{ route('employees.create') }}">
                <i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Add Employee</span><span class="d-sm-none">Add</span>
            </a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. SYSTEM ALERT BANNER (Unregistered Biometric Device Codes) --}}
    {{-- ========================================================= --}}
    @if($unregisteredCodes->isNotEmpty())
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div class="flex-grow-1">
                <strong>{{ $unregisteredCodes->count() }} code(s)</strong> appear in attendance logs but have no employee yet:
                @foreach($unregisteredCodes as $code)
                    <span class="badge text-bg-secondary">{{ $code }}</span>
                @endforeach
                <div class="small mt-1">These show as raw codes in reports until you add them.</div>
            </div>
            <form method="POST" action="{{ route('employees.import-missing') }}">
                @csrf
                <button class="btn btn-sm btn-warning" type="submit">
                    <i class="bi bi-magic me-1"></i>Import missing codes
                </button>
            </form>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- 3. SEARCH & FILTER CARD SECTION --}}
    {{-- ========================================================= --}}
    <div class="card mb-3"style="background-color: #bb4bfcff ;">
        <div class="card-body">
            <form method="GET" action="{{ route('employees.index') }}" class="row g-2 filter-bar">
                {{-- Search Input Field --}}
                <div class="col-12 col-md-7">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input style="background-color: #f0dffaff ;" type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Search by name, code, department, or designation">
                    </div>
                </div>
                {{-- Filter Action Buttons (Search & Clear) --}}
                <div class="col-12 col-md-5 d-flex gap-2">
                    <button class="btn btn-primary flex-fill"type="submit"><i class="bi bi-funnel me-1"></i>Search</button>
                    @if($search)
                        <a class="btn btn-outline-secondary flex-fill" href="{{ route('employees.index') }}">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 4. EMPLOYEES DATA TABLE & ROW ACTION BUTTONS SECTION --}}
    {{-- ========================================================= --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="background-color: #bc94e2ff ;">Employee</th>
                        <th style="background-color: #bc94e2ff ;">Code</th>
                        <th style="background-color: #bc94e2ff ;">Department</th>
                        <th style="background-color: #bc94e2ff ;">Status</th>
                        <th class="text-end" style="background-color: #bc94e2ff ;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($employees as $emp)
                        @php $canManage = auth()->user()?->canManageEmployeeProfile($emp) ?? true; @endphp
                        <tr>
                            {{-- Employee Name, Avatar & Designation --}}
                            <td style="background-color: #f0dffaff ;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar"style="background-color: #f0dffaff ;">{{ strtoupper(mb_substr($emp->name, 0, 1)) }}</span>
                                    <div>
                                        @if($canManage)
                                            <a class="fw-semibold text-decoration-none" href="{{ route('employees.show', $emp) }}">{{ $emp->name }}</a>
                                        @else
                                            <span class="fw-semibold text-secondary">{{ $emp->name }}</span>
                                        @endif
                                        <div class="small text-muted">{{ optional($emp->designation)->name ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            {{-- Biometric / System Code --}}
                            <td style="background-color: #f0dffaff ;"><code>{{ $emp->employee_code }}</code></td>
                            {{-- Department Name --}}
                            <td style="background-color: #f0dffaff ;">{{ optional($emp->department)->name ?: '—' }}</td>
                            {{-- Employment Status Badge --}}
                            <td style="background-color: #f0dffaff ;">
                                @if(($emp->status ?? 'active') === 'active')
                                    <span class="badge text-bg-success-subtle text-success border border-success-subtle">Active</span>
                                @else
                                    <span class="badge text-bg-secondary-subtle text-secondary border">Inactive</span>
                                @endif
                            </td>
                            {{-- Row Action Buttons (View Profile, Edit, Delete) --}}
                            <td class="text-end"style="background-color: #f0dffaff ;">
                                @if($canManage)
                                    {{-- Action Button: View Profile --}}
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('employees.show', $emp) }}" title="View profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    {{-- Action Button: Edit Profile --}}
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('employees.edit', $emp) }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    {{-- Action Button: Delete Employee --}}
                                    <form method="POST" action="{{ route('employees.destroy', $emp) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"
                                                onclick="return confirm('Delete this employee?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    {{-- Restricted Role Action Buttons (Disabled) --}}
                                    <button class="btn btn-sm btn-outline-primary opacity-50" disabled title="Viewing this profile is restricted">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary opacity-50" disabled title="Editing this profile is restricted">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger opacity-50" disabled title="Deleting this profile is restricted">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        {{-- Empty State Message --}}
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                <div class="mb-3">No employees yet.</div>
                                <a class="btn btn-primary btn-sm" href="{{ route('employees.create') }}">
                                    <i class="bi bi-person-plus me-1"></i>Add your first employee
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{-- ========================================================= --}}
        {{-- 5. TABLE PAGINATION LINKS SECTION --}}
        {{-- ========================================================= --}}
        @if($employees->hasPages())
            <div class="card-footer">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection

