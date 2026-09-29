@extends('layouts.app')

@section('title', 'Leave Types')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-tags me-2"></i>Leave Types</h1>
            <div class="text-muted hide-xs-desc">Define leave categories and whether they are paid.</div>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('leave.index') }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Add leave type</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('leave-types.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" maxlength="100" required placeholder="e.g. Maternity Leave">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Annual quota (days)</label>
                            <input type="number" name="annual_quota" class="form-control" min="0" max="365" value="0" required>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_paid" name="is_paid" value="1" checked>
                            <label class="form-check-label" for="is_paid">Paid leave</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-lg me-1"></i>Add type</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul me-1"></i>Existing types</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 38%;">Name</th>
                                <th style="width: 22%;">Quota</th>
                                <th style="width: 20%;">Paid</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveTypes as $type)
                                <tr>
                                    <td>
                                        <input type="text" name="name" form="lt-{{ $type->id }}" class="form-control form-control-sm"
                                               value="{{ $type->name }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="annual_quota" form="lt-{{ $type->id }}" class="form-control form-control-sm"
                                               min="0" max="365" value="{{ $type->annual_quota }}" required>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="is_paid" value="1"
                                                   form="lt-{{ $type->id }}" @checked($type->is_paid)>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary" type="submit" form="lt-{{ $type->id }}" title="Save"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" form="lt-del-{{ $type->id }}"
                                                title="Delete" onclick="return confirm('Delete {{ $type->name }}?{{ $type->leave_requests_count ? ' This also removes '.$type->leave_requests_count.' leave record(s).' : '' }}');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No leave types.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Row forms kept outside the table for valid HTML. --}}
    @foreach($leaveTypes as $type)
        <form id="lt-{{ $type->id }}" method="POST" action="{{ route('leave-types.update', $type) }}" class="d-none">
            @csrf
            @method('PUT')
        </form>
        <form id="lt-del-{{ $type->id }}" method="POST" action="{{ route('leave-types.destroy', $type) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
