@extends('layouts.app')

@section('title', 'Edit Device')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <a href="{{ route('devices.index') }}" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h1 class="h4 mb-1 mt-2"><i class="bi bi-pencil-square me-2"></i>Edit Device</h1>
            <div class="text-muted text-break">{{ $device->name }} ({{ $device->ip_address }}:{{ $device->port }})</div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div>Please fix the errors below.</div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('devices.update', $device) }}">
                        @method('PUT')
                        @include('devices._form', ['device' => $device])
                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                            <a class="btn btn-outline-secondary" href="{{ route('devices.index') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
