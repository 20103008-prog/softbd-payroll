@extends('layouts.app')

@section('title', 'Payslip — '.$slip->employee_name)

@push('head')
<style>
    @media print {
        .navbar, .app-footer, .no-print { display: none !important; }
        body { background: #fff !important; }
        main.container { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
    .payslip-table th { color: #6b7280; font-weight: 600; width: 55%; }
    .payslip-table td { text-align: right; }
</style>
@endpush

@section('content')
    <div class="page-toolbar no-print">
        <div class="page-actions">
            <a class="btn btn-outline-secondary" href="{{ $backRoute ?? route('payroll.show', $run) }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline-success" href="{{ $pdfRoute ?? route('payroll.payslip.pdf', [$run, $slip]) }}">
                <i class="bi bi-filetype-pdf me-1"></i><span class="d-none d-sm-inline">Download PDF</span><span class="d-sm-none">PDF</span>
            </a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="card mx-auto" style="max-width: 780px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    @if($company->logoUrl())
                        <img src="{{ $company->logoUrl() }}" alt="Logo" style="max-height: 56px; max-width: 160px;">
                    @endif
                    <div>
                        <div class="h4 mb-0">{{ $company->displayName() }}</div>
                        @if($company->address)<div class="text-muted small">{{ $company->address }}</div>@endif
                        <div class="text-muted small">
                            @if($company->phone)<span><i class="bi bi-telephone me-1"></i>{{ $company->phone }}</span>@endif
                            @if($company->email)<span class="ms-2"><i class="bi bi-envelope me-1"></i>{{ $company->email }}</span>@endif
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="badge text-bg-primary-subtle text-primary border border-primary-subtle mb-1">Payslip</div>
                    <div class="fw-semibold">{{ $run->periodLabel() }}</div>
                    <div class="text-muted small">Pay period</div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <div class="text-muted small">Employee</div>
                    <div class="fw-semibold fs-5">{{ $slip->employee_name }}</div>
                    <div class="text-muted">Code: {{ $slip->employee_code }}</div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div class="text-muted small">Attendance</div>
                    <div>Present: <strong>{{ $slip->present_days }}</strong> / {{ $slip->working_days }} days</div>
                    <div>Absent: <strong>{{ $slip->absent_days }}</strong>@if($slip->paid_leave_days) &middot; Paid leave: <strong>{{ $slip->paid_leave_days }}</strong>@endif</div>
                    <div>Late: <strong>{{ $slip->late_days }}</strong>@if($slip->half_days) &middot; Half: <strong>{{ $slip->half_days }}</strong>@endif</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="fw-semibold text-success mb-2"><i class="bi bi-plus-circle me-1"></i>Earnings</div>
                    <table class="table table-sm payslip-table">
                        <tr><th>Basic salary</th><td>৳ {{ number_format((float) $slip->monthly_salary, 2) }}</td></tr>
                        @if((float) $slip->overtime_amount > 0)
                            <tr><th>Overtime ({{ rtrim(rtrim(number_format((float) $slip->overtime_hours, 2), '0'), '.') }} hrs)</th><td>৳ {{ number_format((float) $slip->overtime_amount, 2) }}</td></tr>
                        @endif
                        @foreach($slip->items->where('type', 'earning') as $item)
                            <tr><th>{{ $item->name }}</th><td>৳ {{ number_format((float) $item->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="border-top"><th class="fw-semibold">Gross</th><td class="fw-semibold">৳ {{ number_format((float) $slip->gross_salary, 2) }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="fw-semibold text-danger mb-2"><i class="bi bi-dash-circle me-1"></i>Deductions</div>
                    <table class="table table-sm payslip-table">
                        <tr><th>Absent ({{ $slip->absent_days }} days)</th><td>৳ {{ number_format((float) $slip->absent_deduction, 2) }}</td></tr>
                        @if($slip->half_days > 0)
                            <tr><th>Half-day ({{ $slip->half_days }})</th><td>৳ {{ number_format((float) $slip->late_deduction, 2) }}</td></tr>
                        @endif
                        @foreach($slip->items->where('type', 'deduction') as $item)
                            <tr><th>{{ $item->name }}</th><td>৳ {{ number_format((float) $item->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="border-top"><th class="fw-semibold">Total deductions</th><td class="fw-semibold">৳ {{ number_format((float) $slip->total_deductions, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center bg-success-subtle text-success rounded-3 p-3 mt-2">
                <div class="fw-semibold"><i class="bi bi-wallet2 me-1"></i>Net Payable</div>
                <div class="h4 mb-0 fw-bold">৳ {{ number_format((float) $slip->net_salary, 2) }}</div>
            </div>

            <div class="text-muted small mt-4 pt-3 border-top">
                Computed from attendance for {{ $run->periodLabel() }}. This is a system-generated payslip.
            </div>
        </div>
    </div>
@endsection
