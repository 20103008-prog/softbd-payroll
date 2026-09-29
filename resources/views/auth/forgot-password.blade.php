<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password &middot; {{ $companyName ?? config('app.name', 'Attendance') }}</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --accent: #2563eb; --border: #e6e8ec; }
        html[data-bs-theme="dark"] { --accent: #60a5fa; --border: #1f2a3c; --bs-body-bg: #0b1120; --bs-body-color: #e5e7eb; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background: #f6f7f9; }
        html[data-bs-theme="dark"] body { background: #0b1120; }
        .login-shell { width: 100%; max-width: 400px; }
        .login-card { border: 1px solid var(--border); border-radius: .85rem; }
        .btn-brand { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 600; }
        .btn-brand:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .form-control { border-radius: .5rem; }
        .btn { border-radius: .5rem; font-weight: 500; }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5">
                <h1 class="h5 mb-1 fw-semibold">Forgot password</h1>
                <p class="text-muted small mb-4">Enter your email and we’ll send a reset link.</p>

                @if(session('success'))
                    <div class="alert alert-success py-2 small">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-brand w-100">Send reset link</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
