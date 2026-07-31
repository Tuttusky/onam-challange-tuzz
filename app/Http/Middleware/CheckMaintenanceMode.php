<?php

namespace App\Http\Middleware;

use App\Services\WebsiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAdminRequest($request)) {
            return $next($request);
        }

        if (! WebsiteSettingsService::getMaintenanceMode()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'The platform is currently under maintenance. Please try again later.',
            'maintenance_mode' => true,
        ], 503);
    }

    protected function isAdminRequest(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        return str_starts_with($path, 'admin')
            || str_starts_with($path, 'api/admin');
    }
}
