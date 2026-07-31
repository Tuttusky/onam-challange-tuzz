<?php

namespace App\Jobs;

use App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload
    ) {}

    public function handle(AnalyticsService $analyticsService): void
    {
        if (! $analyticsService->isTrackingEnabled()) {
            return;
        }

        $analyticsService->trackEvent(
            (string) $this->payload['event_type'],
            [
                'campaign_id' => $this->payload['campaign_id'] ?? null,
                'player_id' => $this->payload['player_id'] ?? null,
                'source' => $this->payload['source'] ?? null,
                'device' => $this->payload['device'] ?? null,
                'browser' => $this->payload['browser'] ?? null,
                'country' => $this->payload['country'] ?? null,
                'ip' => $this->payload['ip'] ?? null,
                'meta' => $this->payload['meta'] ?? null,
            ]
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Analytics ingest job failed.', [
            'payload' => $this->payload,
            'error' => $exception?->getMessage(),
        ]);
    }
}
