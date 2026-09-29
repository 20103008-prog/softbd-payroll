@extends('layouts.app')

@section('title', 'Summary reports')

@section('content')
    {{-- Page Background Color Style --}}
    <style>
        body {
            background-color: #f1f5f9 !important;
        }
    </style>

    <div class="page-toolbar">
        <div class="min-w-0">
            <h1 class="h4 mb-1" style="font-family: Arial, sans-serif; font-size: 24px; font-weight: bold; color: #1e3a8a; -webkit-text-stroke: 1px #0f172a;">
                <i class="bi bi-bar-chart-line me-2"></i>Summary reports
            </h1>
            <div class="text-muted d-none d-sm-block" style="font-family: Arial, sans-serif; font-style: italic; font-weight: bold; color: #475569;">
                Late arrivals, absences, leave usage and overtime for a period.
            </div>
        </div>
        <div class="page-actions">
            <a class="btn btn-outline-secondary" href="{{ route('reports.attendance') }}" style="font-weight: bold;">
                <i class="bi bi-clipboard-data me-1"></i><span class="d-none d-sm-inline">Daily attendance</span><span class="d-sm-none">Attendance</span>
            </a>
        </div>
    </div>

    @if(! empty($teamScoped))
        <div class="alert alert-info py-2 small" style="font-weight: bold;"><i class="bi bi-info-circle me-1"></i>Showing your department only.</div>
    @endif

    <div class="card mb-3">
        <div class="card-body" style="background-color: #e2e8f0; border: 1px solid #cbd5e1;">
            <form id="summaryFilterForm" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="summaryFrom" style="font-family: Arial, sans-serif; font-weight: bold; color: #334155;">From</label>
                    <input type="date" name="from" id="summaryFrom" class="form-control" value="{{ $filters['from'] }}" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="summaryTo" style="font-family: Arial, sans-serif; font-weight: bold; color: #334155;">To</label>
                    <input type="date" name="to" id="summaryTo" class="form-control" value="{{ $filters['to'] }}" required style="background-color: #ffffff; font-weight: bold; border: 1px solid #94a3b8;">
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-primary w-100" type="submit" style="font-weight: bold; background-color: #1e40af;"><i class="bi bi-funnel me-1"></i>Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3">
            <button type="button" class="card stat-card is-clickable w-100 text-start border @if($filters['tab'] === 'late') is-active @endif" data-jump-tab="late" aria-label="Show late summary" style="background-color: #fee2e2; border-left: 5px solid #ef4444 !important;">
                <div class="card-body">
                    <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #991b1b;">Late days</div>
                    <div class="stat-value text-danger" id="stat-late" style="font-family: Arial, sans-serif; font-weight: bold;">—</div>
                </div>
            </button>
        </div>
        <div class="col-6 col-lg-3">
            <button type="button" class="card stat-card is-clickable w-100 text-start border @if($filters['tab'] === 'absence') is-active @endif" data-jump-tab="absence" aria-label="Show absence summary" style="background-color: #fef3c7; border-left: 5px solid #f59e0b !important;">
                <div class="card-body">
                    <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #92400e;">Absent days</div>
                    <div class="stat-value text-warning" id="stat-absence" style="font-family: Arial, sans-serif; font-weight: bold;">—</div>
                </div>
            </button>
        </div>
        <div class="col-6 col-lg-3">
            <button type="button" class="card stat-card is-clickable w-100 text-start border @if($filters['tab'] === 'leave') is-active @endif" data-jump-tab="leave" aria-label="Show leave summary" style="background-color: #e0f2fe; border-left: 5px solid #06b6d4 !important;">
                <div class="card-body">
                    <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #075985;">Leave days</div>
                    <div class="stat-value text-info" id="stat-leave" style="font-family: Arial, sans-serif; font-weight: bold;">—</div>
                </div>
            </button>
        </div>
        <div class="col-6 col-lg-3">
            <button type="button" class="card stat-card is-clickable w-100 text-start border @if($filters['tab'] === 'overtime') is-active @endif" data-jump-tab="overtime" aria-label="Show overtime summary" style="background-color: #dcfce7; border-left: 5px solid #10b981 !important;">
                <div class="card-body">
                    <div class="stat-label" style="font-family: Arial, sans-serif; font-weight: bold; color: #166534;">Overtime hours</div>
                    <div class="stat-value text-success" id="stat-overtime" style="font-family: Arial, sans-serif; font-weight: bold;">—</div>
                </div>
            </button>
        </div>
    </div>

    <div class="nav nav-scroll" id="summaryTabs" role="tablist">
        <button class="nav-link @if($filters['tab'] === 'late') active @endif" data-bs-toggle="tab" data-bs-target="#tab-late" data-tab="late" type="button" role="tab" style="font-weight: bold;">Late</button>
        <button class="nav-link @if($filters['tab'] === 'absence') active @endif" data-bs-toggle="tab" data-bs-target="#tab-absence" data-tab="absence" type="button" role="tab" style="font-weight: bold;">Absence</button>
        <button class="nav-link @if($filters['tab'] === 'leave') active @endif" data-bs-toggle="tab" data-bs-target="#tab-leave" data-tab="leave" type="button" role="tab" style="font-weight: bold;">Leave</button>
        <button class="nav-link @if($filters['tab'] === 'overtime') active @endif" data-bs-toggle="tab" data-bs-target="#tab-ot" data-tab="overtime" type="button" role="tab" style="font-weight: bold;">Overtime</button>
    </div>

    <div class="tab-content">
        @foreach([
            'late' => ['id' => 'tab-late', 'cols' => 4],
            'absence' => ['id' => 'tab-absence', 'cols' => 6],
            'leave' => ['id' => 'tab-leave', 'cols' => 5],
            'overtime' => ['id' => 'tab-ot', 'cols' => 4],
        ] as $tabKey => $meta)
            <div class="tab-pane fade @if($filters['tab'] === $tabKey) show active @endif" id="{{ $meta['id'] }}" data-tab="{{ $tabKey }}">
                <div class="card" style="background-color: #ffffff; border: 1px solid #cbd5e1;">
                    <div class="table-responsive desktop-only-table">
                        <table class="table align-middle mb-0">
                            <thead>
                                @if($tabKey === 'late')
                                    <tr><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Dept</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Present</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Late days</th></tr>
                                @elseif($tabKey === 'absence')
                                    <tr><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Dept</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Working</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Present</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Paid leave</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Absent</th></tr>
                                @elseif($tabKey === 'leave')
                                    <tr><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Dept</th><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Type</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Days</th><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Paid?</th></tr>
                                @else
                                    <tr><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Employee</th><th style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Dept</th><th class="text-center" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">Present</th><th class="text-end" style="background-color: #334155; color: white; font-family: Arial, sans-serif; font-weight: bold;">OT hours</th></tr>
                                @endif
                            </thead>
                            <tbody id="body-{{ $tabKey }}">
                                <tr class="summary-loading"><td colspan="{{ $meta['cols'] }}" class="text-center text-muted py-4">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body mobile-result-list p-2 p-sm-3" id="mobile-{{ $tabKey }}">
                        <div class="text-center text-muted py-4">Loading…</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var dataUrl = @json($dataUrl);
    var initialTab = @json($filters['tab']);
    var cache = {};
    var controllers = {};

    var emptyMessages = {
        late: 'No late arrivals in this period.',
        absence: 'No absences in this period.',
        leave: 'No approved leave in this period.',
        overtime: 'No overtime in this period.'
    };
    var colspans = { late: 4, absence: 6, leave: 5, overtime: 4 };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function rangeKey() {
        return document.getElementById('summaryFrom').value + '|' + document.getElementById('summaryTo').value;
    }

    function cacheKey(tab) {
        return rangeKey() + '|' + tab;
    }

    function setLoading(tab) {
        var body = document.getElementById('body-' + tab);
        var mobile = document.getElementById('mobile-' + tab);
        if (body) {
            body.innerHTML = '<tr class="summary-loading"><td colspan="' + colspans[tab] + '" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…</td></tr>';
        }
        if (mobile) {
            mobile.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…</div>';
        }
    }

    function setError(tab, message) {
        var body = document.getElementById('body-' + tab);
        var mobile = document.getElementById('mobile-' + tab);
        if (body) {
            body.innerHTML = '<tr><td colspan="' + colspans[tab] + '" class="text-center text-danger py-4">' + escapeHtml(message) + '</td></tr>';
        }
        if (mobile) {
            mobile.innerHTML = '<div class="text-center text-danger py-4">' + escapeHtml(message) + '</div>';
        }
    }

    function updateStat(tab, total) {
        var el = document.getElementById('stat-' + tab);
        if (!el) return;
        if (tab === 'overtime') {
            el.textContent = Number(total).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        } else {
            el.textContent = Number(total).toLocaleString();
        }
    }

    function setActiveStat(tab) {
        document.querySelectorAll('[data-jump-tab]').forEach(function (card) {
            card.classList.toggle('is-active', card.getAttribute('data-jump-tab') === tab);
        });
    }

    function metaItem(label, value, valueClass) {
        return '<div><span class="label">' + escapeHtml(label) + '</span><strong class="' + (valueClass || '') + '">' + value + '</strong></div>';
    }

    function renderMobileCards(tab, rows) {
        var mobile = document.getElementById('mobile-' + tab);
        if (!mobile) return;

        if (!rows.length) {
            mobile.innerHTML = '<div class="text-center text-muted py-4">' + emptyMessages[tab] + '</div>';
            return;
        }

        var html = '';
        rows.forEach(function (row) {
            html += '<div class="mobile-result-card">'
                + '<div class="fw-semibold">' + escapeHtml(row.employee_name) + '</div>'
                + '<div class="small text-muted">' + escapeHtml(row.employee_code)
                + (row.department ? ' · ' + escapeHtml(row.department) : '')
                + '</div><div class="mobile-result-meta">';

            if (tab === 'late') {
                html += metaItem('Present', escapeHtml(row.present_days))
                    + metaItem('Late days', escapeHtml(row.late_days), 'text-danger');
            } else if (tab === 'absence') {
                html += metaItem('Working', escapeHtml(row.working_days))
                    + metaItem('Present', escapeHtml(row.present_days))
                    + metaItem('Paid leave', escapeHtml(row.paid_leave_days))
                    + metaItem('Absent', escapeHtml(row.absent_days), 'text-warning');
            } else if (tab === 'leave') {
                html += metaItem('Type', escapeHtml(row.leave_type))
                    + metaItem('Days', escapeHtml(row.days))
                    + metaItem('Paid', row.is_paid ? 'Yes' : 'No');
            } else if (tab === 'overtime') {
                html += metaItem('Present', escapeHtml(row.present_days))
                    + metaItem('OT hours', Number(row.overtime_hours).toFixed(2), 'text-success');
            }

            html += '</div></div>';
        });
        mobile.innerHTML = html;
    }

    function renderRows(tab, rows) {
        var body = document.getElementById('body-' + tab);
        if (!body) return;

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="' + colspans[tab] + '" class="text-center text-muted py-4">' + emptyMessages[tab] + '</td></tr>';
            renderMobileCards(tab, rows);
            return;
        }

        var html = '';
        rows.forEach(function (row) {
            if (tab === 'late') {
                html += '<tr>'
                    + '<td><div class="fw-semibold">' + escapeHtml(row.employee_name) + '</div><div class="small text-muted">' + escapeHtml(row.employee_code) + '</div></td>'
                    + '<td>' + escapeHtml(row.department || '—') + '</td>'
                    + '<td class="text-center">' + escapeHtml(row.present_days) + '</td>'
                    + '<td class="text-center fw-semibold text-danger">' + escapeHtml(row.late_days) + '</td>'
                    + '</tr>';
            } else if (tab === 'absence') {
                html += '<tr>'
                    + '<td><div class="fw-semibold">' + escapeHtml(row.employee_name) + '</div><div class="small text-muted">' + escapeHtml(row.employee_code) + '</div></td>'
                    + '<td>' + escapeHtml(row.department || '—') + '</td>'
                    + '<td class="text-center">' + escapeHtml(row.working_days) + '</td>'
                    + '<td class="text-center">' + escapeHtml(row.present_days) + '</td>'
                    + '<td class="text-center">' + escapeHtml(row.paid_leave_days) + '</td>'
                    + '<td class="text-center fw-semibold text-warning">' + escapeHtml(row.absent_days) + '</td>'
                    + '</tr>';
            } else if (tab === 'leave') {
                html += '<tr>'
                    + '<td><div class="fw-semibold">' + escapeHtml(row.employee_name) + '</div><div class="small text-muted">' + escapeHtml(row.employee_code) + '</div></td>'
                    + '<td>' + escapeHtml(row.department || '—') + '</td>'
                    + '<td>' + escapeHtml(row.leave_type) + '</td>'
                    + '<td class="text-center fw-semibold">' + escapeHtml(row.days) + '</td>'
                    + '<td>' + (row.is_paid ? 'Yes' : 'No') + '</td>'
                    + '</tr>';
            } else if (tab === 'overtime') {
                html += '<tr>'
                    + '<td><div class="fw-semibold">' + escapeHtml(row.employee_name) + '</div><div class="small text-muted">' + escapeHtml(row.employee_code) + '</div></td>'
                    + '<td>' + escapeHtml(row.department || '—') + '</td>'
                    + '<td class="text-center">' + escapeHtml(row.present_days) + '</td>'
                    + '<td class="text-end fw-semibold text-success">' + Number(row.overtime_hours).toFixed(2) + '</td>'
                    + '</tr>';
            }
        });
        body.innerHTML = html;
        renderMobileCards(tab, rows);
    }

    function syncUrl(tab) {
        var url = new URL(window.location.href);
        url.searchParams.set('from', document.getElementById('summaryFrom').value);
        url.searchParams.set('to', document.getElementById('summaryTo').value);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }

    function loadTab(tab, force) {
        var key = cacheKey(tab);
        setActiveStat(tab);

        if (!force && cache[key]) {
            renderRows(tab, cache[key].rows);
            updateStat(tab, cache[key].total);
            return;
        }

        if (controllers[tab]) {
            controllers[tab].abort();
        }
        controllers[tab] = new AbortController();

        setLoading(tab);

        var params = new URLSearchParams({
            from: document.getElementById('summaryFrom').value,
            to: document.getElementById('summaryTo').value,
            tab: tab
        });

        fetch(dataUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: controllers[tab].signal,
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load ' + tab + ' summary.');
                return res.json();
            })
            .then(function (data) {
                cache[key] = { rows: data.rows || [], total: data.total };
                renderRows(tab, cache[key].rows);
                updateStat(tab, cache[key].total);
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                setError(tab, err.message || 'Unable to load data.');
            });
    }

    function activeTab() {
        var btn = document.querySelector('#summaryTabs .nav-link.active');
        return btn ? btn.getAttribute('data-tab') : 'late';
    }

    function activateTab(tab) {
        var btn = document.querySelector('#summaryTabs [data-tab="' + tab + '"]');
        if (!btn) return;
        var tabTrigger = bootstrap.Tab.getOrCreateInstance(btn);
        tabTrigger.show();
    }

    document.getElementById('summaryFilterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        cache = {};
        ['late', 'absence', 'leave', 'overtime'].forEach(function (tab) {
            document.getElementById('stat-' + tab).textContent = '—';
            setLoading(tab);
        });
        var tab = activeTab();
        syncUrl(tab);
        loadTab(tab, true);
    });

    document.querySelectorAll('#summaryTabs [data-tab]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            var tab = e.target.getAttribute('data-tab');
            syncUrl(tab);
            loadTab(tab, false);
            try { e.target.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' }); } catch (err) {}
        });
    });

    document.querySelectorAll('[data-jump-tab]').forEach(function (card) {
        card.addEventListener('click', function () {
            activateTab(card.getAttribute('data-jump-tab'));
        });
    });

    loadTab(initialTab || activeTab(), true);
})();
</script>
@endpush
