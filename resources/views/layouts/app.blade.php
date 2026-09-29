<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $companyName ?? config('app.name', 'Attendance'))</title>
    {{-- Apply saved theme before paint to avoid a flash of the wrong theme --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', t);
                document.documentElement.classList.add('theme-ready');
            } catch (e) {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #2563eb;
            --bs-primary-rgb: 37,99,235;
            --bs-primary-text-emphasis: #1e40af;
            --bs-primary-bg-subtle: #eff6ff;
            --bs-primary-border-subtle: #bfdbfe;
            --bs-link-color: #2563eb;
            --bs-link-color-rgb: 37,99,235;
            --bs-link-hover-color: #1d4ed8;
            --bs-link-hover-color-rgb: 29,78,216;

            --accent: #2563eb;
            --accent-soft: #eef4ff;
            --surface: #ffffff;
            --app-bg: #f4f6fb;
            --border: #e6e8ec;
            --shadow-sm: 0 1px 2px rgba(16,24,40,.04);
            --shadow-md: 0 8px 24px rgba(16,24,40,.08);

            --app-radius: .75rem;
            --bs-body-color: #1f2430;
            --bs-body-bg: #f4f6fb;
            --bs-border-color: #e6e8ec;
            --bs-body-font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --bs-body-font-size: .95rem;
        }
        html[data-bs-theme="dark"] {
            --bs-primary: #3b82f6;
            --bs-primary-rgb: 59,130,246;
            --accent: #60a5fa;
            --accent-soft: #17263f;
            --surface: #111827;
            --app-bg: #0b1120;
            --border: #1f2a3c;
            --shadow-sm: 0 1px 2px rgba(0,0,0,.25);
            --shadow-md: 0 10px 28px rgba(0,0,0,.35);
            --bs-body-bg: #0b1120;
            --bs-body-color: #e5e7eb;
            --bs-border-color: #1f2a3c;
            --bs-link-color: #93c5fd;
            --bs-link-hover-color: #bfdbfe;
        }

        html { scrollbar-gutter: stable; }
        body {
            min-height: 100vh;
            background:
                radial-gradient(1200px 500px at 10% -10%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 55%),
                radial-gradient(900px 420px at 100% 0%, color-mix(in srgb, #22c55e 8%, transparent), transparent 50%),
                var(--app-bg);
            letter-spacing: -.005em;
        }

        /* Keep hardcoded light utilities legible when dark */
        [data-bs-theme="dark"] .text-dark { color: var(--bs-body-color) !important; }
        [data-bs-theme="dark"] .text-black { color: var(--bs-body-color) !important; }

        body.modal-open { padding-right: 0 !important; }
        .sticky-top { padding-right: 0 !important; }

        h1, h2, h3, h4, h5, .h1, .h2, .h3, .h4, .h5 { letter-spacing: -.02em; }

        /* Buttons */
        .btn { border-radius: .5rem; font-weight: 500; }
        .btn-sm { border-radius: .45rem; }
        .btn-primary {
            --bs-btn-bg: #2563eb; --bs-btn-border-color: #2563eb;
            --bs-btn-hover-bg: #1d4ed8; --bs-btn-hover-border-color: #1d4ed8;
            --bs-btn-active-bg: #1e40af; --bs-btn-active-border-color: #1e40af;
            --bs-btn-disabled-bg: #2563eb; --bs-btn-disabled-border-color: #2563eb;
        }
        .btn-outline-primary {
            --bs-btn-color: #2563eb; --bs-btn-border-color: #bfdbfe;
            --bs-btn-hover-bg: #2563eb; --bs-btn-hover-border-color: #2563eb;
            --bs-btn-active-bg: #1d4ed8; --bs-btn-active-border-color: #1d4ed8;
        }
        .btn-outline-secondary {
            --bs-btn-color: var(--bs-body-color); --bs-btn-border-color: var(--border);
            --bs-btn-hover-bg: var(--accent-soft); --bs-btn-hover-border-color: var(--border);
            --bs-btn-hover-color: var(--bs-body-color);
            --bs-btn-active-bg: var(--accent-soft); --bs-btn-active-color: var(--bs-body-color);
        }
        .btn:focus-visible { box-shadow: 0 0 0 .2rem rgba(37,99,235,.30); }

        /* Navbar — solid sticky bar (no glass blink on reload) */
        .app-navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            transition: box-shadow .2s ease, border-color .2s ease;
        }
        .app-navbar.is-scrolled {
            box-shadow: 0 8px 24px rgba(16,24,40,.08);
            border-bottom-color: transparent;
        }
        .app-navbar.is-nav-pending #smartNav {
            visibility: hidden;
        }
        .navbar-brand { font-weight: 700; letter-spacing: -.01em; color: var(--bs-body-color); }
        .navbar-brand:hover { color: var(--bs-body-color); }
        .brand-mark {
            width: 32px; height: 32px; border-radius: .5rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--accent); color: #fff; font-size: 1rem;
        }
        .brand-logo { height: 30px; width: auto; max-width: 130px; object-fit: contain; }
        .app-nav { align-items: center; }
        .app-nav .nav-link {
            color: var(--bs-secondary-color); border-radius: .5rem; padding: .4rem .7rem;
            font-weight: 500; transition: background-color .15s ease, color .15s ease;
            white-space: nowrap;
        }
        .app-nav .nav-link:hover { color: var(--bs-body-color); background: var(--accent-soft); }
        .app-nav .nav-link.active { color: var(--accent); background: var(--accent-soft); font-weight: 600; }
        .app-nav .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: .65rem;
            box-shadow: 0 12px 32px rgba(16,24,40,.12);
            padding: .4rem;
            min-width: 12.5rem;
        }
        .app-nav .dropdown-item {
            border-radius: .45rem;
            padding: .45rem .7rem;
            font-weight: 500;
        }
        .app-nav .dropdown-item.active,
        .app-nav .dropdown-item:active {
            background: var(--accent-soft);
            color: var(--accent);
        }
        .app-nav .dropdown-header {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--bs-secondary-color);
            font-weight: 700;
        }
        .navbar-toggler { border-color: var(--border); }
        .nav-tools { display: flex; align-items: center; gap: .45rem; }
        .nav-tool-btn {
            color: var(--bs-secondary-color); background: transparent;
            border: 1px solid var(--border); border-radius: .5rem;
            width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;
            transition: background-color .15s ease, color .15s ease;
        }
        .nav-tool-btn:hover { color: var(--bs-body-color); background: var(--accent-soft); }
        .theme-toggle {
            color: var(--bs-secondary-color); background: transparent;
            border: 1px solid var(--border); border-radius: .5rem;
            width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;
            transition: background-color .15s ease, color .15s ease;
        }
        .theme-toggle:hover { color: var(--bs-body-color); background: var(--accent-soft); }
        .user-menu .nav-link { color: var(--bs-body-color); }
        .user-menu .dropdown-toggle::after { display: none; }
        .nav-kbd {
            font-size: .65rem;
            border: 1px solid var(--border);
            border-radius: .3rem;
            padding: .05rem .3rem;
            color: var(--bs-secondary-color);
            background: var(--app-bg);
            margin-left: .35rem;
        }
        .smart-nav-item.is-overflow { display: none !important; }
        #navMoreItem { display: none; }
        #navMoreItem.is-visible { display: block; }
        @media (max-width: 991.98px) {
            .app-navbar.is-nav-pending #smartNav { visibility: visible; }
            .smart-nav-item.is-overflow { display: block !important; }
            #navMoreItem { display: none !important; }
        }
        .cmdk-backdrop {
            position: fixed; inset: 0; z-index: 2000;
            background: rgba(15, 23, 42, .45);
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding: 12vh 1rem 1rem;
        }
        .cmdk-backdrop.is-open { display: flex; }
        .cmdk-panel {
            width: min(520px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: .85rem;
            box-shadow: 0 24px 60px rgba(16,24,40,.28);
            overflow: hidden;
        }
        .cmdk-input {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--border);
            padding: .95rem 1.1rem;
            font-size: 1rem;
            background: transparent;
            color: var(--bs-body-color);
            outline: none;
        }
        .cmdk-list {
            max-height: 320px;
            overflow-y: auto;
            padding: .4rem;
        }
        .cmdk-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            padding: .6rem .75rem;
            border-radius: .5rem;
            color: var(--bs-body-color);
            text-decoration: none;
        }
        .cmdk-item:hover,
        .cmdk-item.is-active {
            background: var(--accent-soft);
            color: var(--accent);
        }
        .cmdk-item .meta {
            margin-left: auto;
            font-size: .72rem;
            color: var(--bs-secondary-color);
        }
        .cmdk-empty {
            padding: 1.25rem;
            text-align: center;
            color: var(--bs-secondary-color);
            font-size: .9rem;
        }
        .cmdk-hint {
            border-top: 1px solid var(--border);
            padding: .45rem .9rem;
            font-size: .72rem;
            color: var(--bs-secondary-color);
            display: flex;
            justify-content: space-between;
            gap: .5rem;
        }

        /* Cards — smart surfaces */
        .card {
            --bs-card-bg: var(--surface);
            border: 1px solid var(--border); border-radius: var(--app-radius);
            box-shadow: var(--shadow-sm);
            transition: box-shadow .18s ease, transform .18s ease, border-color .18s ease;
        }
        .card.card-hover:hover,
        a.card:hover {
            box-shadow: var(--shadow-md);
            border-color: color-mix(in srgb, var(--accent) 28%, var(--border));
            transform: translateY(-1px);
        }
        .card-header {
            background: transparent; border-bottom: 1px solid var(--border);
            font-weight: 600; padding-top: .85rem; padding-bottom: .85rem;
        }

        /* Stat cards */
        .stat-card { position: relative; overflow: hidden; }

        .stat-card::before {
            content: "";
            position: absolute; inset: auto -20% -40% auto;
            width: 120px; height: 120px; border-radius: 50%;
            background: color-mix(in srgb, var(--accent) 8%, transparent);
            pointer-events: none;
        }
        .stat-card .stat-icon {
            position: absolute; top: 1rem; right: 1rem; bottom: auto;
            font-size: 1.05rem; opacity: 1; color: var(--bs-secondary-color);
            width: 2rem; height: 2rem; border-radius: .55rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--accent-soft); color: var(--accent);
        }
        .stat-value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; letter-spacing: -.02em; }
        .stat-label { text-transform: uppercase; letter-spacing: .05em; font-size: .7rem; color: var(--bs-secondary-color); font-weight: 600; }
        .stat-hint { font-size: .75rem; color: var(--bs-secondary-color); margin-top: .35rem; }
        .stat-progress {
            height: .28rem; border-radius: 999px; background: color-mix(in srgb, var(--border) 80%, transparent);
            margin-top: .75rem; overflow: hidden;
        }
        .stat-progress > span {
            display: block; height: 100%; border-radius: inherit;
            background: linear-gradient(90deg, var(--accent), #60a5fa);
        }
        a.stat-card { text-decoration: none; color: inherit; display: block; }

        /* Avatars */
        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 600; color: var(--accent); background: var(--accent-soft);
            flex-shrink: 0;
        }

        /* Tables */
        .table { --bs-table-border-color: var(--border); }
        .table > :not(caption) > * > * { padding: .7rem .9rem; }
        .table thead th { font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); font-weight: 600; }
        .table-hover > tbody > tr { transition: background-color .12s ease; }
        .table-hover > tbody > tr:hover > * { --bs-table-bg-state: color-mix(in srgb, var(--accent-soft) 70%, transparent); }

        /* Forms */
        .form-control, .form-select { border-radius: .55rem; border-color: var(--border); }
        .form-control:focus, .form-select:focus { border-color: #93c5fd; box-shadow: 0 0 0 .2rem rgba(37,99,235,.16); }
        .form-label { font-weight: 500; margin-bottom: .35rem; }
        .input-group-text { background: color-mix(in srgb, var(--app-bg) 80%, var(--surface)); border-color: var(--border); }

        /* Badges */
        .badge { font-weight: 600; }

        /* Smart chips / context */
        .smart-chip {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .28rem .65rem; border-radius: 999px;
            background: var(--surface); border: 1px solid var(--border);
            font-size: .78rem; color: var(--bs-secondary-color); font-weight: 500;
        }
        .smart-chip .dot {
            width: .45rem; height: .45rem; border-radius: 50%;
            background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.15);
        }
        .smart-chip.warn .dot { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.15); }
        .smart-chip.danger .dot { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.15); }

        /* Empty / soft panels */
        .empty-state {
            text-align: center; padding: 2.25rem 1rem; color: var(--bs-secondary-color);
        }
        .empty-state i { font-size: 1.75rem; opacity: .7; display: block; margin-bottom: .5rem; }

        /* Activity list */
        .activity-row {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem 0;
        }
        .activity-row + .activity-row { border-top: 1px solid var(--border); }

        /* Top route progress */
        #routeProgress {
            position: fixed; top: 0; left: 0; height: 2px; width: 0;
            z-index: 2000; background: linear-gradient(90deg, var(--accent), #22c55e);
            opacity: 0; transition: width .25s ease, opacity .2s ease;
            pointer-events: none;
        }
        #routeProgress.is-active { opacity: 1; }

        /* Floating flash notifications */
        .flash-stack { position: fixed; top: 4.5rem; right: 1rem; z-index: 1090; width: min(360px, calc(100vw - 2rem)); }
        .flash-stack .alert { box-shadow: var(--shadow-md); border-radius: .7rem; border: 1px solid var(--border); }

        footer.app-footer { color: var(--bs-secondary-color); font-size: .82rem; }
        code { color: #d63384; }

        /* Modal polish */
        .modal-content { border: 1px solid var(--border); border-radius: .85rem; box-shadow: 0 24px 60px rgba(16,24,40,.22); }
        .modal.fade .modal-dialog { transform: translateY(16px); transition: transform .2s ease, opacity .2s ease; }
        .modal.show .modal-dialog { transform: none; }
        .modal-backdrop.fade { transition: opacity .2s ease; }
        .modal-backdrop.show { opacity: .35; }

        main.container { max-width: 1140px; }
        @media (min-width: 1400px) {
            main.container { max-width: 1240px; }
        }

        /* Responsive shell */
        html, body { max-width: 100%; overflow-x: clip; }
        img, svg, video, canvas { max-width: 100%; height: auto; }
        .navbar-brand span {
            max-width: min(42vw, 180px);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .page-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .page-toolbar .page-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .page-toolbar .min-w-0 { min-width: 0; }
        .table-responsive {
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }
        .table-sticky-first th:first-child,
        .table-sticky-first td:first-child {
            position: sticky;
            left: 0;
            z-index: 1;
            background: var(--surface);
        }
        .table-sticky-first thead th:first-child { z-index: 2; }
        .filter-bar .form-control,
        .filter-bar .form-select { min-width: 0; }
        .btn-icon-label { white-space: nowrap; }
        .nav-scroll {
            display: flex;
            flex-wrap: nowrap;
            gap: .35rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            margin-bottom: 1rem;
            padding-bottom: .15rem;
        }
        .nav-scroll::-webkit-scrollbar { display: none; }
        .nav-scroll .nav-link {
            white-space: nowrap;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: .4rem .9rem;
            color: var(--bs-secondary-color);
            background: var(--surface);
        }
        .nav-scroll .nav-link.active {
            color: #fff;
            background: var(--accent);
            border-color: var(--accent);
        }
        .stat-card.is-clickable { cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
        button.stat-card {
            appearance: none;
            -webkit-appearance: none;
            background: var(--surface);
            color: inherit;
            text-align: left;
            font: inherit;
            padding: 0;
        }
        .stat-card.is-clickable:hover,
        .stat-card.is-clickable.is-active {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 28%, transparent);
        }
        .stat-card.is-clickable:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 .2rem rgba(37,99,235,.30);
        }
        .mobile-result-list { display: none; }
        .mobile-result-card {
            border: 1px solid var(--border);
            border-radius: var(--app-radius);
            background: var(--surface);
            padding: .85rem 1rem;
        }
        .mobile-result-card + .mobile-result-card { margin-top: .65rem; }
        .mobile-result-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .55rem .75rem;
            margin-top: .65rem;
        }
        .mobile-result-meta .label {
            display: block;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--bs-secondary-color);
            font-weight: 600;
        }
        .card-header.flex-wrap,
        .card-header.d-flex { gap: .5rem; }
        .card-body,
        .card-header { overflow-wrap: anywhere; }
        pre, code { word-break: break-word; }
        img, svg, video { max-width: 100%; height: auto; }
        .modal-body pre { white-space: pre-wrap; }

        @media (max-width: 991.98px) {
            .app-nav { padding-bottom: .5rem; }
            .app-nav .nav-link { padding: .55rem .75rem; }
            .navbar-collapse .dropdown-menu {
                width: 100%;
                border: 0;
                box-shadow: none;
                padding-left: .5rem;
            }
            .modal-dialog { margin: .75rem; }
        }

        @media (max-width: 767.98px) {
            main.container {
                padding-top: 1rem;
                padding-bottom: 1.25rem;
                padding-left: .85rem;
                padding-right: .85rem;
            }
            .stat-value { font-size: 1.2rem; }
            .stat-card .card-body { padding: .85rem; }
            .stat-card .stat-icon { display: none; }
            .table > :not(caption) > * > * { padding: .55rem .65rem; font-size: .9rem; }
            .page-toolbar .page-actions { width: 100%; }
            .page-toolbar .page-actions > .btn,
            .page-toolbar .page-actions > form {
                flex: 1 1 calc(50% - .25rem);
            }
            .page-toolbar .page-actions > .btn,
            .page-toolbar .page-actions > form .btn {
                width: 100%;
            }
            .flash-stack { top: 4rem; right: .75rem; left: .75rem; width: auto; }
            .desktop-only-table { display: none !important; }
            .mobile-result-list { display: block; }
            .hide-xs-desc { display: none !important; }
            .card-header { padding-left: 1rem; padding-right: 1rem; }
            .pagination { flex-wrap: wrap; justify-content: center; gap: .25rem; }
            .btn-group { flex-wrap: wrap; }
            /* Compact action icons in dense tables */
            .table .btn-sm { padding: .25rem .45rem; }
            .form-text { font-size: .75rem; }
            .alert { font-size: .9rem; }
            .card.mx-auto { max-width: 100% !important; }
            .payslip-table th { width: auto !important; }
        }

        @media (max-width: 575.98px) {
            .brand-logo { max-width: 96px; }
            .navbar-brand { font-size: 1rem; }
            .h3, h1.h3 { font-size: 1.35rem; }
            .h4 { font-size: 1.15rem; }
            .input-group > .form-control,
            .input-group > .form-select { min-width: 0; }
            .table .btn-sm .d-none-xs { display: none; }
        }

        ::selection { background: rgba(37,99,235,.18); }
    </style>
    @stack('head')
</head>
<body>
<div id="routeProgress" aria-hidden="true"></div>
<nav class="navbar navbar-expand-lg app-navbar sticky-top is-nav-pending" id="appNavbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            @if(! empty($companyLogo))
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="brand-logo">
            @else
                <span class="brand-mark"><i class="bi bi-building-check"></i></span>
            @endif
            <span>{{ $companyName ?? config('app.name', 'Attendance') }}</span>
        </a>

        <div class="d-flex align-items-center gap-2 order-lg-last ms-auto ms-lg-0">
            <button type="button" class="nav-tool-btn" id="navSearchBtn" title="Quick search (Ctrl+K)" aria-label="Quick search">
                <i class="bi bi-search"></i>
            </button>
            <button class="theme-toggle" style="background-color: #1927eeff; color: white;" id="themeToggle" type="button" title="Toggle light / dark" aria-label="Toggle light or dark theme">
                <i class="bi" id="themeToggleIcon"></i>
            </button>
            <script>
                (function () {
                    var icon = document.getElementById('themeToggleIcon');
                    if (!icon) return;
                    var t = document.documentElement.getAttribute('data-bs-theme') || 'light';
                    icon.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
                })();
            </script>
            @auth
                <div class="navbar-nav user-menu d-none d-lg-block">
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar" style="width:30px;height:30px;font-size:.78rem;">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><span class="dropdown-item-text small"><span class="fw-semibold d-block">{{ auth()->user()->name }}</span><span class="text-muted">{{ auth()->user()->email }}</span></span></li>
                            @canany(['manage-users', 'manage-settings', 'view-audit'])
                                <li><hr class="dropdown-divider"></li>
                            @endcanany
                            @can('manage-settings')
                                <li>
                                    <a class="dropdown-item @if(request()->is('settings/company')) active @endif" href="{{ route('settings.company') }}">
                                        <i class="bi bi-building me-1"></i>Company Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item @if(request()->is('settings/late-policy')) active @endif" href="{{ route('settings.late-policy') }}">
                                        <i class="bi bi-alarm me-1"></i>Late Policy
                                    </a>
                                </li>
                            @endcan
                            @can('manage-users')
                                <li>
                                    <a class="dropdown-item @if(request()->is('users') || request()->is('users/*')) active @endif" href="{{ route('users.index') }}">
                                        <i class="bi bi-shield-lock me-1"></i>Users &amp; Roles
                                    </a>
                                </li>
                            @endcan
                            @can('view-audit')
                                <li>
                                    <a class="dropdown-item @if(request()->is('audit') || request()->is('audit/*')) active @endif" href="{{ route('audit.index') }}">
                                        <i class="bi bi-journal-text me-1"></i>Audit log
                                    </a>
                                </li>
                            @endcan
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger no-loading">
                                        <i class="bi bi-box-arrow-right me-1"></i>Sign out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            @endauth
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="topnav">
            <div class="navbar-nav app-nav ms-lg-auto gap-1 me-lg-2" id="smartNav">
                @php
                    $portalOnly = auth()->check() && auth()->user()->isPortalOnly() && auth()->user()->hasEmployeeProfile();
                @endphp

                @unless($portalOnly)
                    <a class="nav-link smart-nav-item @if(request()->is('/')) active @endif" href="{{ url('/') }}" data-priority="1" data-label="Dashboard" data-keywords="home dashboard">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                @endunless

                @can('manage-employees')
                    <div class="nav-item dropdown smart-nav-item @if(request()->is('employees*') || request()->is('roster*') || request()->is('departments*')) active @endif" style="background-color: #99eff1ff; color: white;"data-priority="2">
                        <a class="nav-link dropdown-toggle @if(request()->is('employees*') || request()->is('roster*') || request()->is('departments*')) active @endif" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-people me-1"></i>People
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Workforce</h6></li>
                            <li><a class="dropdown-item @if(request()->is('employees') || request()->is('employees/*')) active @endif" href="{{ route('employees.index') }}" data-cmdk data-label="Employees" data-keywords="staff directory people"><i class="bi bi-people me-1"></i>Employees</a></li>
                            <li><a class="dropdown-item @if(request()->is('departments*')) active @endif" href="{{ route('organization.index') }}" data-cmdk data-label="Departments & Designations" data-keywords="organization structure"><i class="bi bi-diagram-3 me-1"></i>Departments</a></li>
                            <li><a class="dropdown-item @if(request()->is('roster*')) active @endif" href="{{ route('roster.index') }}" data-cmdk data-label="Roster / Duty" data-keywords="shifts duty schedule"><i class="bi bi-calendar2-range me-1"></i>Roster / Duty</a></li>
                        </ul>
                    </div>
                @endcan

                @can('view-reports')
                    <div class="nav-item dropdown smart-nav-item" style="background-color: #5291f0ff; color:black;"data-priority="3">
                        <a class="nav-link dropdown-toggle @if(request()->is('reports/*')) active @endif" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-clipboard-data me-1"></i>Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item @if(request()->is('reports/attendance*')) active @endif" href="{{ route('reports.attendance') }}" data-cmdk data-label="Daily attendance" data-keywords="report punches day"><i class="bi bi-calendar-check me-1"></i>Daily attendance</a></li>
                            <li><a class="dropdown-item @if(request()->is('reports/summary*')) active @endif" href="{{ route('reports.summary') }}" data-cmdk data-label="Summaries" data-keywords="late absence overtime leave report"><i class="bi bi-bar-chart-line me-1"></i>Summaries</a></li>
                        </ul>
                    </div>
                @endcan

                @can('manage-leave')
                    <div class="nav-item dropdown smart-nav-item"style="background-color: #6c47c7ff; color: coffee;" data-priority="4">
                        <a class="nav-link dropdown-toggle @if(request()->is('leave*') || request()->is('leave-types*') || request()->is('holidays*')) active @endif" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar-heart me-1"></i>Leave
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item @if(request()->routeIs('leave.index', 'leave.create', 'leave.store', 'leave.approve', 'leave.reject', 'leave.destroy')) active @endif" href="{{ route('leave.index') }}" data-cmdk data-label="Leave requests" data-keywords="approve reject leave"><i class="bi bi-inbox me-1"></i>Requests</a></li>
                            <li><a class="dropdown-item @if(request()->is('leave/balances*')) active @endif" href="{{ route('leave.balances') }}" data-cmdk data-label="Leave balances" data-keywords="quota remaining"><i class="bi bi-calendar2-week me-1"></i>Balances</a></li>
                            <li><a class="dropdown-item @if(request()->is('leave-types*')) active @endif" href="{{ route('leave-types.index') }}" data-cmdk data-label="Leave types" data-keywords="categories paid unpaid"><i class="bi bi-tags me-1"></i>Types</a></li>
                            <li><a class="dropdown-item @if(request()->is('holidays*')) active @endif" href="{{ route('holidays.index') }}" data-cmdk data-label="Holidays" data-keywords="public holiday calendar"><i class="bi bi-calendar-event me-1"></i>Holidays</a></li>
                        </ul>
                    </div>
                @endcan

                @can('manage-payroll')
                    <div class="nav-item dropdown smart-nav-item" data-priority="5">
                        <a class="nav-link dropdown-toggle @if(request()->is('payroll*')) active @endif" style="background-color: #b6983eff; color: green;" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-cash-coin me-1"></i>Payroll
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item @if(request()->routeIs('payroll.index', 'payroll.store', 'payroll.show', 'payroll.lock', 'payroll.unlock', 'payroll.export', 'payroll.payslip', 'payroll.payslip.pdf', 'payroll.destroy')) active @endif" href="{{ route('payroll.index') }}" data-cmdk data-label="Payroll runs" data-keywords="salary generate payslip"><i class="bi bi-cash-stack me-1"></i>Runs</a></li>
                            <li><a class="dropdown-item @if(request()->is('payroll/salaries*')) active @endif" href="{{ route('payroll.salaries') }}" data-cmdk data-label="Employee salaries" data-keywords="structure overtime rate"><i class="bi bi-wallet2 me-1"></i>Salaries</a></li>
                        </ul>
                    </div>
                @endcan
               {{-- Device Navigation Menu 
                @can('manage-devices')
                    <div class="nav-item dropdown smart-nav-item" data-priority="6">
                        <a class="nav-link dropdown-toggle @if(request()->is('devices*')) active @endif" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-hdd-network me-1"></i>Devices
                        </a>
                        <ul class="dropdown-menu"style="background-color: #c00e58ff; color: white;">
                            <li><a class="dropdown-item @if(request()->is('devices') || request()->is('devices/create') || request()->is('devices/*/edit')) active @endif" href="{{ route('devices.index') }}" data-cmdk data-label="Devices" data-keywords="terminal device hardware"><i class="bi bi-hdd-network me-1"></i>All devices</a></li>
                        </ul>
                    </div>
                @endcan
                --}}

                @auth
                    @if(auth()->user()->employee_id)
                        @if($portalOnly)
                            <a class="nav-link smart-nav-item @if(request()->is('me/attendance*')) active @endif"style="background-color: #f069a1ff; color:red;" href="{{ route('me.attendance') }}" data-priority="1" data-label="My attendance" data-keywords="portal punches">
                                <i class="bi bi-clock-history me-1"></i>My attendance
                            </a>
                            <a class="nav-link smart-nav-item @if(request()->is('me/leave*')) active @endif" href="{{ route('me.leave') }}" data-priority="2" data-label="My leave" data-keywords="portal request">
                                <i class="bi bi-calendar-heart me-1"></i>My leave
                            </a>
                            <a class="nav-link smart-nav-item @if(request()->is('me/payslips*')) active @endif" href="{{ route('me.payslips') }}" data-priority="3" data-label="My payslips" data-keywords="portal salary pdf">
                                <i class="bi bi-receipt me-1"></i>My payslips
                            </a>
                        @else
                            <div class="nav-item dropdown smart-nav-item" data-priority="2">
                                <a class="nav-link dropdown-toggle @if(request()->is('me*')) active @endif" style="background-color: #ee7dacff; color: coffee;"href="#" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-badge me-1"></i>My portal
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item @if(request()->is('me/attendance*')) active @endif" href="{{ route('me.attendance') }}" data-cmdk data-label="My attendance" data-keywords="portal punches"><i class="bi bi-clock-history me-1"></i>My attendance</a></li>
                                    <li><a class="dropdown-item @if(request()->is('me/leave*')) active @endif" href="{{ route('me.leave') }}" data-cmdk data-label="My leave" data-keywords="portal request"><i class="bi bi-calendar-heart me-1"></i>My leave</a></li>
                                    <li><a class="dropdown-item @if(request()->is('me/payslips*')) active @endif" href="{{ route('me.payslips') }}" data-cmdk data-label="My payslips" data-keywords="portal salary pdf"><i class="bi bi-receipt me-1"></i>My payslips</a></li>
                                </ul>
                            </div>
                        @endif
                    @endif
                @endauth
                 {{-- Overflow bucket filled by JS on desktop --}}
                {{--
               
                <div class="nav-item dropdown" id="navMoreItem">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="navMoreToggle">
                        <i class="bi bi-three-dots me-1"></i>More
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" id="navMoreMenu"></ul>
                </div>
                --}}
            </div>

            {{-- Mobile account links (desktop uses avatar menu) --}}
            @auth
                <div class="d-lg-none border-top pt-3 mt-2">
                    <div class="small text-muted px-2 mb-2">{{ auth()->user()->name }}</div>
                    @can('manage-settings')
                        <a class="nav-link" href="{{ route('settings.company') }}"><i class="bi bi-building me-1"></i>Company Profile</a>
                        <a class="nav-link" href="{{ route('settings.late-policy') }}"><i class="bi bi-alarm me-1"></i>Late Policy</a>
                    @endcan
                    @can('manage-users')
                        <a class="nav-link" href="{{ route('users.index') }}"><i class="bi bi-shield-lock me-1"></i>Users &amp; Roles</a>
                    @endcan
                    @can('view-audit')
                        <a class="nav-link" href="{{ route('audit.index') }}"><i class="bi bi-journal-text me-1"></i>Audit log</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}" class="px-2 mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 no-loading"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button>
                    </form>
                </div>
            @endauth
        </div>
    </div>
</nav>

{{-- Quick jump (Ctrl/Cmd+K) --}}
<div class="cmdk-backdrop" id="cmdk" role="dialog" aria-modal="true" aria-label="Quick search" hidden>
    <div class="cmdk-panel">
        <input type="search" class="cmdk-input" id="cmdkInput" placeholder="Jump to a page…" autocomplete="off" spellcheck="false">
        <div class="cmdk-list" id="cmdkList"></div>
        <div class="cmdk-hint">
            <span>Navigate with ↑ ↓ · Enter to open</span>
            <span><kbd class="nav-kbd">Esc</kbd> close</span>
        </div>
    </div>
</div>

<div class="flash-stack">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ session('warning') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-x-circle-fill"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>

<main class="container py-4">
    @yield('content')
</main>

<footer class="app-footer text-center py-4">
    &copy; {{ date('Y') }} {{ $companyName ?? config('app.name', 'Attendance') }}
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Theme toggle (persisted in localStorage)
    (function () {
        var toggle = document.getElementById('themeToggle');
        var icon = document.getElementById('themeToggleIcon');
        function syncIcon() {
            var t = document.documentElement.getAttribute('data-bs-theme');
            if (icon) icon.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
        syncIcon();
        if (toggle) {
            toggle.addEventListener('click', function () {
                var cur = document.documentElement.getAttribute('data-bs-theme');
                var next = cur === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                try { localStorage.setItem('theme', next); } catch (e) {}
                syncIcon();
            });
        }
    })();

    // Sticky navbar scroll elevation
    (function () {
        var nav = document.getElementById('appNavbar');
        if (!nav) return;
        function onScroll() {
            nav.classList.toggle('is-scrolled', window.scrollY > 4);
        }
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    })();

    // Smart overflow: move items that don't fit into "More"
    (function () {
        var bar = document.getElementById('appNavbar');
        var nav = document.getElementById('smartNav');
        var moreItem = document.getElementById('navMoreItem');
        var moreMenu = document.getElementById('navMoreMenu');

        function reveal() {
            if (bar) bar.classList.remove('is-nav-pending');
        }
        // Never leave the nav hidden if JS stalls
        setTimeout(reveal, 400);

        if (!nav || !moreItem || !moreMenu) {
            reveal();
            return;
        }

        var items = Array.prototype.slice.call(nav.querySelectorAll('.smart-nav-item'));
        items.sort(function (a, b) {
            return Number(a.getAttribute('data-priority') || 99) - Number(b.getAttribute('data-priority') || 99);
        });

        function isDesktop() {
            return window.matchMedia('(min-width: 992px)').matches;
        }

        function reset() {
            items.forEach(function (el) { el.classList.remove('is-overflow'); });
            moreMenu.innerHTML = '';
            moreItem.classList.remove('is-visible');
            var moreToggle = document.getElementById('navMoreToggle');
            if (moreToggle) moreToggle.classList.remove('active');
        }

        function layout() {
            reset();
            if (!isDesktop()) {
                reveal();
                return;
            }

            nav.appendChild(moreItem);

            var available = nav.clientWidth;
            var used = 0;
            var overflow = [];
            var reserve = 88;

            items.forEach(function (el) {
                used += el.offsetWidth + 4;
            });

            if (used > available) {
                var remaining = items.slice().reverse();
                for (var i = 0; i < remaining.length; i++) {
                    var el = remaining[i];
                    if (Number(el.getAttribute('data-priority')) <= 1) continue;
                    el.classList.add('is-overflow');
                    overflow.push(el);

                    used = 0;
                    items.forEach(function (it) {
                        if (!it.classList.contains('is-overflow')) used += it.offsetWidth + 4;
                    });
                    if (used + reserve <= available) break;
                }
            }

            if (overflow.length) {
                moreItem.classList.add('is-visible');
                var anyActive = false;
                overflow.reverse().forEach(function (el) {
                    var label = '';
                    var link = el.matches('a') ? el : el.querySelector(':scope > .nav-link');
                    if (link) {
                        label = (link.textContent || '').replace(/\s+/g, ' ').trim();
                        if (link.classList.contains('active')) anyActive = true;
                    }
                    if (el.classList.contains('dropdown')) {
                        var sub = el.querySelectorAll('.dropdown-menu .dropdown-item');
                        if (sub.length) {
                            var header = document.createElement('li');
                            header.innerHTML = '<h6 class="dropdown-header">' + label.replace(/More/i, '').trim() + '</h6>';
                            moreMenu.appendChild(header);
                            sub.forEach(function (item) {
                                var li = document.createElement('li');
                                var a = item.cloneNode(true);
                                a.classList.remove('dropdown-toggle');
                                li.appendChild(a);
                                moreMenu.appendChild(li);
                                if (item.classList.contains('active')) anyActive = true;
                            });
                            return;
                        }
                    }
                    var li = document.createElement('li');
                    var a = document.createElement('a');
                    a.className = 'dropdown-item' + (link && link.classList.contains('active') ? ' active' : '');
                    a.href = link ? link.getAttribute('href') : '#';
                    a.innerHTML = link ? link.innerHTML : label;
                    li.appendChild(a);
                    moreMenu.appendChild(li);
                });
                var moreToggle = document.getElementById('navMoreToggle');
                if (moreToggle && anyActive) moreToggle.classList.add('active');
            }

            reveal();
        }

        var t;
        var ready = false;
        function schedule() {
            clearTimeout(t);
            t = setTimeout(function () {
                layout();
                ready = true;
            }, ready ? 40 : 0);
        }

        window.addEventListener('resize', schedule);

        var started = false;
        function startOnce() {
            if (started) return;
            started = true;
            schedule();
        }
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(startOnce);
        }
        setTimeout(startOnce, 80);
    })();

    // Quick jump command palette (Ctrl/Cmd+K)
    (function () {
        var backdrop = document.getElementById('cmdk');
        var input = document.getElementById('cmdkInput');
        var list = document.getElementById('cmdkList');
        var openBtn = document.getElementById('navSearchBtn');
        if (!backdrop || !input || !list) return;

        var pages = [
            { label: 'Dashboard', href: @json(url('/')), keywords: 'home dashboard', group: 'General' }
        ];

        document.querySelectorAll('[data-cmdk]').forEach(function (el) {
            pages.push({
                label: el.getAttribute('data-label') || (el.textContent || '').trim(),
                href: el.getAttribute('href'),
                keywords: el.getAttribute('data-keywords') || '',
                group: 'Pages'
            });
        });

        // Deduplicate by href
        var seen = {};
        pages = pages.filter(function (p) {
            if (!p.href || seen[p.href]) return false;
            seen[p.href] = true;
            return true;
        });

        var activeIndex = 0;
        var filtered = pages.slice();

        function open() {
            backdrop.hidden = false;
            backdrop.classList.add('is-open');
            input.value = '';
            render('');
            setTimeout(function () { input.focus(); }, 10);
        }
        function close() {
            backdrop.classList.remove('is-open');
            backdrop.hidden = true;
        }
        function render(q) {
            q = (q || '').trim().toLowerCase();
            filtered = pages.filter(function (p) {
                if (!q) return true;
                return (p.label + ' ' + p.keywords).toLowerCase().indexOf(q) !== -1;
            });
            activeIndex = 0;
            if (!filtered.length) {
                list.innerHTML = '<div class="cmdk-empty">No matching pages</div>';
                return;
            }
            list.innerHTML = filtered.map(function (p, i) {
                return '<a class="cmdk-item' + (i === 0 ? ' is-active' : '') + '" href="' + p.href + '" data-idx="' + i + '">' +
                    '<i class="bi bi-arrow-return-right"></i><span>' + p.label + '</span>' +
                    '<span class="meta">' + (p.group || '') + '</span></a>';
            }).join('');
        }
        function move(delta) {
            if (!filtered.length) return;
            activeIndex = (activeIndex + delta + filtered.length) % filtered.length;
            list.querySelectorAll('.cmdk-item').forEach(function (el, i) {
                el.classList.toggle('is-active', i === activeIndex);
            });
            var cur = list.querySelector('.cmdk-item.is-active');
            if (cur) cur.scrollIntoView({ block: 'nearest' });
        }
        function go() {
            if (!filtered.length) return;
            window.location.href = filtered[activeIndex].href;
        }

        if (openBtn) openBtn.addEventListener('click', open);
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) close();
        });
        input.addEventListener('input', function () { render(input.value); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Enter') { e.preventDefault(); go(); }
            else if (e.key === 'Escape') { e.preventDefault(); close(); }
        });
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (backdrop.classList.contains('is-open')) close();
                else open();
            }
            if (e.key === 'Escape' && backdrop.classList.contains('is-open')) close();
        });
    })();

    // Top route progress on internal navigation
    (function () {
        var bar = document.getElementById('routeProgress');
        if (!bar) return;
        function start() {
            bar.classList.add('is-active');
            bar.style.width = '18%';
            requestAnimationFrame(function () { bar.style.width = '72%'; });
        }
        function done() {
            bar.style.width = '100%';
            setTimeout(function () {
                bar.classList.remove('is-active');
                bar.style.width = '0';
            }, 220);
        }
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            if (a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            try {
                var url = new URL(a.href, window.location.origin);
                if (url.origin !== window.location.origin) return;
                if (url.pathname === window.location.pathname && url.search === window.location.search) return;
            } catch (err) { return; }
            start();
        });
        window.addEventListener('pageshow', done);
    })();

    // Auto-dismiss flash notifications after a few seconds
    setTimeout(function () {
        document.querySelectorAll('.flash-stack .alert-dismissible').forEach(function (el) {
            bootstrap.Alert.getOrCreateInstance(el).close();
        });
    }, 6000);

    // Show a spinner on the button that submitted a form
    document.addEventListener('submit', function (e) {
        var btn = e.submitter;
        if (!btn || btn.classList.contains('no-loading') || btn.disabled) return;
        setTimeout(function () {
            var w = btn.offsetWidth;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.style.minWidth = w + 'px';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }, 0);
    });
</script>
@stack('scripts')
</body>
</html>
