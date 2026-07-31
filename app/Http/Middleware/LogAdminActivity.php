<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if (! auth('admin')->check()) {
            return $response;
        }

        $route = $request->route();
        $action = $route?->getName() ?? $request->path();

        ActivityLog::query()->create([
            'admin_id' => auth('admin')->id(),
            'action' => strtoupper($request->method()).' '.$action,
            'model_type' => null,
            'model_id' => null,
            'properties' => [
                'route' => $route?->getName(),
                'path' => $request->path(),
                'input' => $request->except(['password', 'password_confirmation', '_token', '_method']),
            ],
            'ip' => $request->ip(),
        ]);

        return $response;
    }
}
