@extends('layouts.app')

@section('title', 'My leave')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-calendar-heart me-2"></i>My leave</h1>
            <div class="text-muted">Balances for {{ $year }} and your requests.</div>
        </div>
        <div class="page-actions">
            <a class="btn btn-primary" href="{{ route('me.leave.create') }}"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">Request leave</span><span class="d-sm-none">Request</span></a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach($balances as $row)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-semibold">{{ $row['type']->name }}</div>
                        <div class="small text-muted mb-1">{{ $row['type']->is_paid ? 'Paid' : 'Unpaid' }}</div>
                        <div class="stat-value" style="font-size:1.25rem;">{{ max(0, $row['remaining']) }}</div>
                        <div class="small text-muted">of {{ $row['quota'] ?: '∞' }} left · {{ $row['used'] }} used</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">My requests</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Dates</th>
                        <th class="text-center">Days</th>
                        <th>Status</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->leaveType?->name }}</td>
                            <td>{{ $req->start_date->format('M j') }} – {{ $req->end_date->format('M j, Y') }}</td>
                            <td class="text-center">{{ $req->days }}</td>
                            <td>
                                @if($req->status === 'approved')
                                    <span class="badge text-bg-success-subtle text-success border border-success-subtle">Approved</span>
                                @elseif($req->status === 'rejected')
                                    <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>
                                @else
                                    <span class="badge text-bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $req->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No leave requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div class="card-body">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
