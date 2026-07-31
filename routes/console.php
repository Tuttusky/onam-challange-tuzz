<?php

use App\Jobs\GenerateSitemapJob;
use App\Jobs\PurgeExpiredPottuCustomImagesJob;
use App\Jobs\RebuildLeaderboardJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RebuildLeaderboardJob)->hourly();
Schedule::job(new GenerateSitemapJob)->daily();
Schedule::job(new PurgeExpiredPottuCustomImagesJob)->daily();
