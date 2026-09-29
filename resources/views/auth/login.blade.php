<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in &middot; {{ $companyName ?? config('app.name', 'Attendance') }}</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-body-font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --accent: #2563eb;
            --border: #e6e8ec;
        }
        html[data-bs-theme="dark"] {
            --accent: #60a5fa; --border: #1f2a3c;
            --bs-body-bg: #0b1120; --bs-body-color: #e5e7eb;
        }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1.5rem; background: var(--bs-body-bg, #f6f7f9); letter-spacing: -.005em;
        }
        html:not([data-bs-theme="dark"]) body { background: #f6f7f9; }
        .login-shell { width: 100%; max-width: 400px; }
        .login-card { border: 1px solid var(--border); border-radius: .85rem; box-shadow: 0 1px 2px rgba(16,24,40,.05); }
        .brand-badge {
            width: 48px; height: 48px; border-radius: .7rem;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff; background: var(--accent);
        }
        .btn { border-radius: .5rem; font-weight: 500; }
        .btn-brand { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 600; }
        .btn-brand:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .form-control { border-radius: .5rem; }
        .form-control:focus, .input-group:focus-within .form-control {
            border-color: #93c5fd; box-shadow: 0 0 0 .2rem rgba(37,99,235,.16);
        }
        .form-label { font-weight: 500; margin-bottom: .35rem; }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    @if(! empty($companyLogo))
                        <img src="{{ $companyLogo }}" alt="{{ $companyName ?? config('app.name', 'Attendance') }}" class="mb-3" style="max-height:52px;max-width:170px;object-fit:contain;">
                    @else
                        <span class="brand-badge mb-3"><i class="bi bi-fingerprint"></i></span>
                    @endif
                    <h1 class="h5 mb-1 fw-semibold">{{ $companyName ?? config('app.name', 'Attendance') }}</h1>
                    <p class="text-muted small mb-0">Sign in to your account</p>
                </div>

                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-check-circle-fill"></i>
                        <div class="small">{{ session('success') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <div class="small">{{ $errors->first() }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                   placeholder="you@example.com" autofocus required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control"
                                   placeholder="Your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1">
                                <i class="bi bi-eye" id="togglePwdIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-brand w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
                    </button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot your password?</a>
                </div>
            </div>
        </div>
        <p class="text-center small text-muted mt-3 mb-0">
            &copy; {{ date('Y') }} {{ $companyName ?? config('app.name', 'Attendance') }}
        </p>
    </div>

    <script>
        document.getElementById('togglePwd').addEventListener('click', function () {
            var input = document.getElementById('password');
            var icon = document.getElementById('togglePwdIcon');
            var isPwd = input.type === 'password';
            input.type = isPwd ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPwd);
            icon.classList.toggle('bi-eye-slash', isPwd);
        });
    </script>
</body>
</html>
