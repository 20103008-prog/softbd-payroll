@extends('layouts.app')

@section('title', 'My payslips')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-receipt me-2"></i>My payslips</h1>
            <div class="text-muted hide-xs-desc">{{ $employee->name }} &middot; Generated and locked payroll only</div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slips as $slip)
                        <tr>
                            <td class="fw-semibold">{{ $slip->payrollRun?->periodLabel() }}</td>
                            <td class="text-end">৳ {{ number_format((float) $slip->gross_salary, 2) }}</td>
                            <td class="text-end text-danger">৳ {{ number_format((float) $slip->total_deductions, 2) }}</td>
                            <td class="text-end fw-semibold">৳ {{ number_format((float) $slip->net_salary, 2) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('me.payslip', $slip) }}" title="View"><i class="bi bi-eye"></i></a>
                                <a class="btn btn-sm btn-outline-success" href="{{ route('me.payslip.pdf', $slip) }}" title="Download PDF"><i class="bi bi-filetype-pdf"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No payslips available yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($slips->hasPages())
            <div class="card-body">{{ $slips->links() }}</div>
        @endif
    </div>
@endsection
