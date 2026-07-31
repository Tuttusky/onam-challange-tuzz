<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/admin.css'])
</head>
<body class="admin-login-page">
    <div class="glass-card admin-login-card">
        <div class="text-center mb-4">
            <small class="text-uppercase" style="color: var(--admin-gold); letter-spacing: .1em;">Campaign Platform</small>
            <h2 class="text-white mt-2">Admin Sign In</h2>
        </div>

        @include('admin.partials.alerts')

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label text-muted-admin" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-admin-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>
