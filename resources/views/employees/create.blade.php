@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <a href="{{ route('employees.index') }}" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h1 class="h4 mb-1 mt-2"><i class="bi bi-person-plus me-2"></i>Add Employee</h1>
            <div class="text-muted hide-xs-desc">Link a device code to a real person.</div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-x-circle-fill mt-1"></i>
            <div>
                <div class="fw-semibold">Please fix the errors below:</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('employees.store') }}">
                        @include('employees._form')
                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Create</button>
                            <a class="btn btn-outline-secondary" href="{{ route('employees.index') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
