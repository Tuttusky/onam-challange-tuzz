<?php

namespace App\Jobs;

use App\Services\Pottu\PottuCustomImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgeExpiredPottuCustomImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PottuCustomImageService $customImageService): void
    {
        $customImageService->purgeExpired();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Pottu custom image purge job failed.', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
