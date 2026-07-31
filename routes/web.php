<?php

use App\Http\Controllers\SpaController;
use App\Services\SeoService;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', function (SeoService $seo) {
    return response($seo->generateSitemap(), 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
});

Route::get('/robots.txt', function (SeoService $seo) {
    return response($seo->generateRobots(), 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
});

Route::get('/run-migrations-secret', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        $sqlitePath = database_path('database.sqlite');
        if (!file_exists($sqlitePath)) {
            @touch($sqlitePath);
        }

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

        $mainCampaign = \App\Models\Campaign::where('slug', 'sundarikk-pottu-thodal')->first();
        if ($mainCampaign) {
            \App\Models\PottuImage::where('campaign_id', '!=', $mainCampaign->id)->update(['campaign_id' => $mainCampaign->id]);
            \App\Models\Campaign::where('id', '!=', $mainCampaign->id)->where('type', 'sundarikk_pottu')->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Database migrated, seeded, and campaign photos consolidated successfully!',
            'db_connection' => config('database.default'),
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'db_connection' => config('database.default'),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/{any?}', [SpaController::class, 'index'])
    ->where('any', '^(?!admin(?:/|$)|api(?:/|$)|run-migrations-secret).*$');

