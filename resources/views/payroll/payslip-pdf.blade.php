<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip — {{ $slip->employee_name }} — {{ $run->periodLabel() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2430; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 12px; margin-bottom: 16px; }
        .row { width: 100%; }
        .col { display: inline-block; vertical-align: top; width: 48%; }
        .col-right { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { text-align: left; color: #6b7280; font-weight: 600; padding: 4px 0; }
        td { text-align: right; padding: 4px 0; }
        .section-title { font-weight: 700; margin-top: 12px; }
        .net { background: #ecfdf5; padding: 10px 12px; margin-top: 16px; }
        .net strong { font-size: 16px; }
        .half { width: 48%; display: inline-block; vertical-align: top; }
    </style>
</head>
<body>
    <div class="header">
        <div class="row">
            <div class="col">
                <h1>{{ $company->displayName() }}</h1>
                @if($company->address)<div class="muted">{{ $company->address }}</div>@endif
                <div class="muted">
                    @if($company->phone){{ $company->phone }}@endif
                    @if($company->email) · {{ $company->email }}@endif
                </div>
            </div>
            <div class="col col-right">
                <strong>Payslip</strong><br>
                {{ $run->periodLabel() }}
            </div>
        </div>
    </div>

    <div class="row" style="margin-bottom: 16px;">
        <div class="col">
            <div class="muted">Employee</div>
            <strong style="font-size: 14px;">{{ $slip->employee_name }}</strong><br>
            Code: {{ $slip->employee_code }}
        </div>
        <div class="col col-right">
            <div class="muted">Attendance</div>
            Present: {{ $slip->present_days }} / {{ $slip->working_days }} days<br>
            Absent: {{ $slip->absent_days }}@if($slip->paid_leave_days) · Paid leave: {{ $slip->paid_leave_days }}@endif<br>
            Late: {{ $slip->late_days }}@if($slip->half_days) · Half: {{ $slip->half_days }}@endif
        </div>
    </div>

    <div class="half">
        <div class="section-title">Earnings</div>
        <table>
            <tr><th>Basic salary</th><td>{{ number_format((float) $slip->monthly_salary, 2) }}</td></tr>
            @if((float) $slip->overtime_amount > 0)
                <tr><th>Overtime ({{ number_format((float) $slip->overtime_hours, 2) }} hrs)</th><td>{{ number_format((float) $slip->overtime_amount, 2) }}</td></tr>
            @endif
            @foreach($slip->items->where('type', 'earning') as $item)
                <tr><th>{{ $item->name }}</th><td>{{ number_format((float) $item->amount, 2) }}</td></tr>
            @endforeach
            <tr><th><strong>Gross</strong></th><td><strong>{{ number_format((float) $slip->gross_salary, 2) }}</strong></td></tr>
        </table>
    </div>
    <div class="half" style="margin-left: 3%;">
        <div class="section-title">Deductions</div>
        <table>
            <tr><th>Absent ({{ $slip->absent_days }} days)</th><td>{{ number_format((float) $slip->absent_deduction, 2) }}</td></tr>
            @if($slip->half_days > 0)
                <tr><th>Half-day ({{ $slip->half_days }})</th><td>{{ number_format((float) $slip->late_deduction, 2) }}</td></tr>
            @endif
            @foreach($slip->items->where('type', 'deduction') as $item)
                <tr><th>{{ $item->name }}</th><td>{{ number_format((float) $item->amount, 2) }}</td></tr>
            @endforeach
            <tr><th><strong>Total deductions</strong></th><td><strong>{{ number_format((float) $slip->total_deductions, 2) }}</strong></td></tr>
        </table>
    </div>

    <div class="net">
        <span>Net Payable</span>
        <strong style="float: right;">৳ {{ number_format((float) $slip->net_salary, 2) }}</strong>
        <div style="clear: both;"></div>
    </div>

    <p class="muted" style="margin-top: 24px; font-size: 10px;">
        Computed from attendance for {{ $run->periodLabel() }}. System-generated payslip.
    </p>
</body>
</html>
