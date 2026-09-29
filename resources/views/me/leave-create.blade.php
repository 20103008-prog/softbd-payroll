@extends('layouts.app')

@section('title', 'Request leave')

@section('content')
    <div class="mb-3">
        <a href="{{ route('me.leave') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <div class="card mx-auto" style="max-width: 560px;">
        <div class="card-header">Request leave</div>
        <div class="card-body">
            <p class="text-muted small">Submitting as <strong>{{ $employee->name }}</strong> ({{ $employee->employee_code }}). Requests start as pending.</p>

            @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('me.leave.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Leave type</label>
                    <select name="leave_type_id" class="form-select" required>
                        <option value="">Select…</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                                {{ $type->name }} @if($type->annual_quota)({{ $type->annual_quota }} days/yr)@endif
                            </option>
                        @endforeach
                    </select>
                </div>
@php
    $minDate = \Illuminate\Support\Carbon::tomorrow()->toDateString();
@endphp

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Start</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $minDate) }}" min="{{ $minDate }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $minDate) }}" min="{{ $minDate }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="Optional">
                </div>
                <button type="submit" class="btn btn-primary w-100">Submit request</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var startEl = document.querySelector('input[name="start_date"]');
        var endEl = document.querySelector('input[name="end_date"]');
        if (!startEl || !endEl) return;

        var minDate = "{{ $minDate }}";

        function updateMin() {
            var val = startEl.value;
            if (val && val > minDate) {
                endEl.min = val;
                if (endEl.value < val) {
                    endEl.value = val;
                }
            } else {
                endEl.min = minDate;
            }
        }

        startEl.addEventListener('change', updateMin);
        updateMin();
    })();
</script>
@endpush
