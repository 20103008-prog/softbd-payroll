@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-journal-text me-2"></i>Audit log</h1>
            <div class="text-muted hide-xs-desc">Who changed punches, leave, salaries and users.</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end filter-bar">
                <div class="col-12 col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Summary or user">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All</option>
                        @foreach($actions as $act)
                            <option value="{{ $act }}" @selected($filters['action'] === $act)>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary flex-fill" href="{{ route('audit.index') }}">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Summary</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap small">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            <td>
                                @if($log->user)
                                    <div class="fw-semibold">{{ $log->user->name }}</div>
                                    <div class="small text-muted">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </td>
                            <td><code class="small">{{ $log->action }}</code></td>
                            <td>{{ $log->summary }}</td>
                            <td class="small text-muted">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No audit entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-body">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
