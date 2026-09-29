@extends('layouts.app')

@section('title', 'Departments & Designations')

@section('content')
    {{-- ========================================================= --}}
    {{-- 1. PAGE TOOLBAR & BACK BUTTON SECTION --}}
    {{-- ========================================================= --}}
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: black; -webkit-text-stroke: 1px #8370b9ff;">
                <i class="bi bi-diagram-3 me-2"></i>Departments &amp; Designations
            </h1>
            <div class="text-muted hide-xs-desc" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold;">
                Organize your workforce structure. Used in employee profiles.
            </div>
        </div>
        <div class="page-actions">
            {{-- Navigation Back Button --}}
            <a class="btn btn-outline-secondary" href="{{ route('employees.index') }}" style="font-weight: bold;">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 2. VALIDATION ERROR ALERTS SECTION --}}
    {{-- ========================================================= --}}
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="row g-3">
        {{-- ========================================================= --}}
        {{-- 3. LEFT COLUMN: DEPARTMENTS MANAGEMENT CARD --}}
        {{-- ========================================================= --}}
        <div class="col-lg-6">
            <div class="card h-100" style="background-color: #bb4bfcff;">
                <div class="card-header" style="background-color: #afa2ceff; color: white; font-family: Arial, sans-serif; font-weight: bold; font-size: 18px;">
                    <i class="bi bi-building"></i>Departments
                </div>
                <div class="card-body" style="background-color: #f0dffaff;">
                    {{-- Add New Department Input Form --}}
                    <form method="POST" action="{{ route('departments.store') }}" class="input-group mb-3">
                        @csrf
                        <span class="input-group-text" style="background-color: #bc94e2ff; color: black;"><i class="bi bi-plus-lg"></i></span>
                        <input type="text" name="name" class="form-control" maxlength="100" placeholder="New department name" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #bc94e2ff;">
                        <button class="btn btn-primary" type="submit" style="font-weight: bold;">Add</button>
                    </form>

                    {{-- Departments Data Table & Inline Edit/Delete Buttons --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="background-color: #bc94e2ff; color: black; font-family: Arial, sans-serif; font-weight: bold;">Department Name</th>
                                    <th class="text-center" style="background-color: #bc94e2ff; color: black; font-family: Arial, sans-serif; font-weight: bold;">Staff</th>
                                    <th class="text-end" style="background-color: #bc94e2ff; color: black; font-family: Arial, sans-serif; font-weight: bold;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $dept)
                                    <tr>
                                        <td style="background-color: #f0dffaff;">
                                            <input type="text" name="name" form="dept-{{ $dept->id }}" class="form-control form-control-sm"
                                                   value="{{ $dept->name }}" required style="font-family: Arial, sans-serif; font-weight: bold; background-color: #ffffff;">
                                        </td>
                                        <td class="text-center" style="background-color: #f0dffaff;">
                                            <span class="badge text-bg-light border" style="font-size: 14px; font-weight: bold; background-color: #9ef8d6ff !important; color: black;">{{ $dept->employees_count }}</span>
                                        </td>
                                        <td class="text-end" style="background-color: #f0dffaff;">
                                            {{-- Save Department Name Button --}}
                                            <button class="btn btn-sm btn-primary" type="submit" form="dept-{{ $dept->id }}" title="Save"><i class="bi bi-check-lg"></i></button>
                                            {{-- Delete Department Button --}}
                                            <button class="btn btn-sm btn-outline-danger" type="submit" form="dept-del-{{ $dept->id }}"
                                                    title="Delete" onclick="return confirm('Delete {{ $dept->name }}? Employees keep their record but lose this department.');"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4" style="background-color: #f0dffaff;">No departments yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- 4. RIGHT COLUMN: DESIGNATIONS MANAGEMENT CARD --}}
        {{-- ========================================================= --}}
        <div class="col-lg-6">
            <div class="card h-100" style="background-color: #e9bba0ff;">
                <div class="card-header" style="background-color: #c0ae6dff; color: black; font-family: Arial, sans-serif; font-weight: bold; font-size: 18px;">
                    <i class="bi bi-briefcase me-1"></i>Designations
                </div>
                <div class="card-body" style="background-color: #fceadeff;">
                    {{-- Add New Designation Input Form --}}
                    <form method="POST" action="{{ route('designations.store') }}" class="input-group mb-3">
                        @csrf
                        <span class="input-group-text" style="background-color: #b6a979ff; color: black;"><i class="bi bi-plus-lg"></i></span>
                        <input type="text" name="name" class="form-control" maxlength="100" placeholder="New designation name" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #b3a77cff;">
                        <button class="btn btn-warning" type="submit" style="font-weight: bold; color: black;">Add</button>
                    </form>

                    {{-- Designations Data Table & Inline Edit/Delete Buttons --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="background-color: #ccbe8bff; color: black; font-family: Arial, sans-serif; font-weight: bold;">Designation Name</th>
                                    <th class="text-center" style="background-color: #e2c24eff; color: black; font-family: Arial, sans-serif; font-weight: bold;">Staff</th>
                                    <th class="text-end" style="background-color: #e2c24eff; color: black; font-family: Arial, sans-serif; font-weight: bold;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($designations as $desig)
                                    <tr>
                                        <td style="background-color: #fceadeff;">
                                            <input type="text" name="name" form="desig-{{ $desig->id }}" class="form-control form-control-sm"
                                                   value="{{ $desig->name }}" required style="font-family: Arial, sans-serif; font-weight: bold; background-color: #ffffff;">
                                        </td>
                                        {{-- staff count --}}
                                        <td class="text-center" style="background-color: #fceadeff;">
                                            <span class="badge text-bg-light border" style="font-size: 14px; font-weight: bold; background-color: #f0b569ff !important; color: black;">{{ $desig->employees_count }}</span>
                                        </td>
                                        <td class="text-end" style="background-color: #fceadeff;">
                                            {{-- Save Designation Name Button --}}
                                            <button class="btn btn-sm btn-primary" type="submit" form="desig-{{ $desig->id }}" title="Save"><i class="bi bi-check-lg"></i></button>
                                            {{-- Delete Designation Button --}}
                                            <button class="btn btn-sm btn-outline-danger" type="submit" form="desig-del-{{ $desig->id }}"
                                                    title="Delete" onclick="return confirm('Delete {{ $desig->name }}?');"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4" style="background-color: #fceadeff;">No designations yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 5. HIDDEN FORM TARGETS FOR INLINE TABLE EDITING --}}
    {{-- ========================================================= --}}
    @foreach($departments as $dept)
        <form id="dept-{{ $dept->id }}" method="POST" action="{{ route('departments.update', $dept) }}" class="d-none">@csrf @method('PUT')</form>
        <form id="dept-del-{{ $dept->id }}" method="POST" action="{{ route('departments.destroy', $dept) }}" class="d-none">@csrf @method('DELETE')</form>
    @endforeach
    @foreach($designations as $desig)
        <form id="desig-{{ $desig->id }}" method="POST" action="{{ route('designations.update', $desig) }}" class="d-none">@csrf @method('PUT')</form>
        <form id="desig-del-{{ $desig->id }}" method="POST" action="{{ route('designations.destroy', $desig) }}" class="d-none">@csrf @method('DELETE')</form>
    @endforeach
@endsection


