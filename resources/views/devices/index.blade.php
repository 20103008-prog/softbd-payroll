@extends('layouts.app')

@section('title', 'Devices')

@section('content')
    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1"><i class="bi bi-hdd-network me-2"></i>Devices</h1>
            <div class="text-muted hide-xs-desc">Configure attendance devices, IP address, port, and active status.</div>
        </div>
        <div class="page-actions">
            <a class="btn btn-primary" href="{{ route('devices.create') }}">
                <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">Add Device</span><span class="d-sm-none">Add</span>
            </a>
        </div>
    </div>

    @if(! config('zkteco.live_sync'))
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-pause-circle-fill fs-5"></i>
            <div>
                <strong>Live device sync is off.</strong>
                LAN pull, ADMS push, JSON API, and Sync now will not store punches.
                Use an employee profile <strong>Check In / Check Out</strong> button instead
                (same <code>attendance_logs</code> pipeline as a device).
                Set <code>ZKTECO_LIVE_SYNC=true</code> in <code>.env</code> to re-enable.
            </div>
        </div>
    @endif

    @php
        $socketsOk = extension_loaded('sockets');
        $phpIni = php_ini_loaded_file() ?: '(none)';
        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : '';
        $sapi = php_sapi_name();
    @endphp
    @if(! $socketsOk)
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                <strong>PHP sockets extension is off for this web request.</strong>
                “Sync now” and scheduled pull need <code>extension=sockets</code> in the <strong>same</strong> <code>php.ini</code> that this site uses (not necessarily the one CLI uses).
                <ul class="mb-2 mt-2">
                    <li>Edit the file below, ensure <code>extension=sockets</code> is uncommented, save.</li>
                    <li><strong>Restart</strong> <code>php artisan serve</code> or Apache — PHP only reads php.ini on startup.</li>
                </ul>
                <p class="small mb-1"><strong>This request:</strong> SAPI <code>{{ $sapi }}</code></p>
                <p class="small mb-1"><strong>Loaded php.ini:</strong> <code>{{ $phpIni }}</code></p>
                @if($phpBinary)
                    <p class="small mb-0"><strong>PHP binary:</strong> <code>{{ $phpBinary }}</code></p>
                @endif
                <p class="small mt-2 mb-0">Until sockets work here, you can still use <code>POST /api/zkteco/attendance</code> (push). @if(app()->environment('local'))<a href="{{ url('/_debug/php') }}">PHP diagnostics</a>@endif</p>
            </div>
        </div>
    @endif

    <div class="alert alert-info d-flex gap-2">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>
            <strong>Local Sync</strong> pulls from the device IP on your LAN (e.g. <code>192.168.68.201</code>).
            Your PC and the K40-A must be on the same network and the device must respond to
            <code>ping</code>. A red “unreachable” badge below means the terminal is offline or the IP changed — fix that first, then Sync.
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Device</th>
                        <th class="text-nowrap">SN</th>
                        <th class="text-nowrap">IP Address</th>
                        <th>Port</th>
                        <th>Status</th>
                        <th class="text-nowrap">Last seen</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($devices as $d)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="background: {{ $d->is_active ? '#16a34a' : '#94a3b8' }};">
                                        <i class="bi bi-hdd"></i>
                                    </span>
                                    <span class="fw-semibold">{{ $d->name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if($d->serial_number)
                                    <code>{{ $d->serial_number }}</code>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <code>{{ $d->ip_address }}</code>
                                @if(isset($reachability[$d->id]))
                                    @if($reachability[$d->id])
                                        <span class="badge text-bg-success-subtle text-success border border-success-subtle ms-1">online</span>
                                    @else
                                        <span class="badge text-bg-danger-subtle text-danger border border-danger-subtle ms-1">unreachable</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $d->port }}</td>
                            <td>
                                @if($d->is_active)
                                    <span class="badge rounded-pill text-bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                                @endif
                            </td>
                            <td class="text-nowrap small text-muted">
                                {{ $d->last_seen_at ? $d->last_seen_at->diffForHumans() : '—' }}
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('devices.edit', $d) }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('devices.destroy', $d) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete"
                                            onclick="return confirm('Delete this device?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-hdd-network fs-1 d-block mb-2 opacity-50"></i>
                                <div class="mb-3">No devices configured yet.</div>
                                <a class="btn btn-primary btn-sm" href="{{ route('devices.create') }}">
                                    <i class="bi bi-plus-lg me-1"></i>Add your first device
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($devices->hasPages())
            <div class="card-footer">
                {{ $devices->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-sync-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (!btn) return;
            btn.disabled = true;
            form.querySelector('.js-spinner')?.classList.remove('d-none');
            form.querySelector('.js-sync-icon')?.classList.add('d-none');
        });
    });
</script>
@endpush
