<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>
<body class="admin-body">
<div class="admin-wrapper">
    @include('admin.partials.sidebar')

    <div class="admin-main">
        <header class="admin-topbar">
            <div>
                <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-sidebar-toggle>
                    <i class="bi bi-list"></i>
                </button>
                <span class="ms-2 text-white fw-semibold">@yield('page-title', 'Dashboard')</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted-admin small">{{ auth('admin')->user()->name ?? '' }}</span>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            @include('admin.partials.alerts')
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
