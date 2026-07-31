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

Route::get('/{any?}', [SpaController::class, 'index'])
    ->where('any', '^(?!admin(?:/|$)|api(?:/|$)).*$');
