<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (filter_var(env('LOAD_TEST_MODE', false), FILTER_VALIDATE_BOOL)) {
            $middleware->api(remove: 'throttle:api');
        }

        $middleware->alias([
            'player.session' => \App\Http\Middleware\ValidatePlayerSession::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'log.admin.activity' => \App\Http\Middleware\LogAdminActivity::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->booting(function (): void {
        RateLimiter::for('api', function (Request $request) {
            if (config('app.load_test_mode')) {
                return Limit::none();
            }

            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    })
    ->create();
