@extends('layouts.app')

@section('title', 'Users & Roles')

@section('content')
    {{-- ========================================================================= --}}
    {{-- 1. PAGE HEADER / TOOLBAR & ADD USER BUTTON --}}
    {{-- ========================================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            {{-- Main Page Title --}}
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: #1e3a8a; -webkit-text-stroke: 1px #0f172a;">
                <i class="bi bi-shield-lock me-2"></i>Users &amp; Roles
            </h1>
            {{-- Page Subtitle / Description --}}
            <div class="text-muted hide-xs-desc" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold; color: #475569;">
                Create logins and control who can edit attendance data.
            </div>
        </div>
        
        {{-- Primary Action Button: Add System User --}}
        <div class="page-actions">
            <a class="btn btn-primary" href="{{ route('users.create') }}" style="font-weight: bold;">
                <i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Add User</span><span class="d-sm-none">Add</span>
            </a>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 2. ROLES & PERMISSIONS MATRIX TABLE --}}
    {{-- ========================================================================= --}}
    <div class="card mb-3" style="background-color: #ffffff; border: 1px solid #cbd5e1;">
        {{-- Matrix Card Header --}}
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold; font-size: 16px;">
            <span><i class="bi bi-grid-3x3-gap me-1"></i>Roles &amp; permissions</span>
            <span class="text-white-50 small">{{ count($roles) }} roles &middot; {{ count($permissions) }} permissions</span>
        </div>

        {{-- Permission Matrix Table --}}
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-center table-sticky-first">
                <thead>
                    <tr>
                        <th class="text-start" style="min-width: 160px; background-color: #475569; color: white; font-family: Arial, sans-serif; font-weight: bold;">Permission</th>
                        {{-- Column Header for Each System Role (Super Admin, Admin, HR, Manager) --}}
                        @foreach($roles as $roleKey => $roleLabel)
                            <th style="min-width: 110px; background-color: #475569; color: white; font-family: Arial, sans-serif; font-weight: bold;">
                                @php
                                    $badge = match($roleKey) {
                                        \App\Models\User::ROLE_SUPER_ADMIN => 'text-bg-primary',
                                        \App\Models\User::ROLE_ADMIN => 'text-bg-info',
                                        \App\Models\User::ROLE_HR => 'text-bg-success',
                                        \App\Models\User::ROLE_MANAGER => 'text-bg-warning',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp
                                <span class="badge rounded-pill {{ $badge }}" style="font-weight: bold;">{{ $roleLabel }}</span>
                                <div class="text-white-50 fw-normal small mt-1 d-none d-lg-block" style="max-width: 150px; white-space: normal; text-transform: none; letter-spacing: normal;">
                                    {{ $roleDescriptions[$roleKey] ?? '' }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- Loop Through Permissions and Display Allowed Checks (✓ / --) --}}
                    @foreach($permissions as $ability => $label)
                        <tr>
                            <td class="text-start" style="background-color: #f8fafc;">
                                <span class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $label }}</span>
                                <div class="text-muted small"><code>{{ $ability }}</code></div>
                            </td>
                            @foreach($roles as $roleKey => $roleLabel)
                                <td style="background-color: #f8fafc;">
                                    @if(in_array($ability, $rolePermissionMap[$roleKey] ?? [], true))
                                        <i class="bi bi-check-circle-fill text-success" title="Allowed"></i>
                                    @else
                                        <i class="bi bi-dash text-muted opacity-50" title="Not allowed"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- Matrix Information Footer --}}
        <div class="card-footer text-muted small" style="background-color: #f1f5f9; font-weight: bold;">
            <i class="bi bi-info-circle me-1"></i>Permissions are granted by role. Change a user's role below to adjust their access.
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3. USER SEARCH & FILTER FORM --}}
    {{-- ========================================================================= --}}
    <div class="card mb-3">
        <div class="card-body" style="background-color: #f1f5f9; border: 1px solid #cbd5e1;">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 filter-bar">
                {{-- Search Input Field --}}
                <div class="col-12 col-md-7">
                    <div class="input-group">
                        <span class="input-group-text" style="background-color: #cbd5e1;"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Search by name or email" style="background-color: #ffffff; font-weight: bold;">
                    </div>
                </div>
                {{-- Search Action Buttons (Submit & Clear) --}}
                <div class="col-12 col-md-5 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit" style="font-weight: bold; background-color: #1e40af;"><i class="bi bi-funnel me-1"></i>Search</button>
                    @if($search)
                        <a class="btn btn-outline-secondary flex-fill" href="{{ route('users.index') }}" style="font-weight: bold;">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 4. SYSTEM USERS LIST TABLE --}}
    {{-- ========================================================================= --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    {{-- User Table Columns Header --}}
                    <thead>
                    <tr>
                        <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">User</th>
                        <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Email</th>
                        <th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Role</th>
                        <th class="text-end" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Actions</th>
                    </tr>
                    </thead>
                    
                    {{-- User Data Table Rows --}}
                    <tbody>
                    @forelse($users as $u)
                        <tr>
                            {{-- 1. User Name & Avatar Column --}}
                            <td style="background-color: #f8fafc;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="background-color: #cbd5e1; color: #0f172a; font-weight: bold;">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                    <span class="fw-semibold" style="font-family: Arial, sans-serif; font-weight: bold;">{{ $u->name }}</span>
                                    @if($u->id === auth()->id())
                                        <span class="badge text-bg-light border" style="font-weight: bold;">You</span>
                                    @endif
                                </div>
                            </td>

                            {{-- 2. User Email Column --}}
                            <td style="background-color: #f8fafc; font-weight: bold;">{{ $u->email }}</td>

                            {{-- 3. User Role Badge Column --}}
                            <td style="background-color: #f8fafc;">
                                @php
                                    $uBadge = match($u->role) {
                                        \App\Models\User::ROLE_SUPER_ADMIN => 'text-bg-primary',
                                        \App\Models\User::ROLE_ADMIN => 'text-bg-info',
                                        \App\Models\User::ROLE_HR => 'text-bg-success',
                                        \App\Models\User::ROLE_MANAGER => 'text-bg-warning',
                                        default => 'text-bg-secondary',
                                    };
                                @endphp
                                <span class="badge rounded-pill {{ $uBadge }}" style="font-weight: bold;">
                                    @if($u->isSuperAdmin())<i class="bi bi-star-fill me-1"></i>@endif{{ $u->roleLabel() }}
                                </span>
                            </td>

                            {{-- 4. User Action Buttons (Edit / Delete) --}}
                            <td class="text-end">
                                {{-- Edit User Button --}}
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit', $u) }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Delete User Button (Prevent Deleting Logged-in Account) --}}
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $u) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"
                                                onclick="return confirm('Delete this user?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        {{-- Empty State Row --}}
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                <div class="mb-3">No users found.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 5. PAGINATION LINKS --}}
        {{-- ========================================================================= --}}
        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>

@endsection
