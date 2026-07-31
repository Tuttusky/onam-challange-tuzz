<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\LeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RebuildLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $campaignId = null,
        public ?string $period = null,
        public ?string $metric = null
    ) {}

    public function handle(LeaderboardService $leaderboardService): void
    {
        $periods = $this->period ? [$this->period] : ['daily', 'weekly', 'monthly', 'overall'];
        $metrics = $this->metric ? [$this->metric] : [
            'most_shared', 'most_invites', 'highest_match',
            'most_created', 'most_won', 'highest_accuracy', 'longest_chain',
        ];

        $campaigns = $this->campaignId
            ? Campaign::query()->whereKey($this->campaignId)->get()
            : collect([null])->merge(Campaign::query()->where('status', 'active')->get());

        $totalCreated = 0;

        foreach ($campaigns as $campaign) {
            foreach ($periods as $period) {
                foreach ($metrics as $metric) {
                    $totalCreated += $leaderboardService->generateSnapshot(
                        $period,
                        $metric,
                        $campaign instanceof Campaign ? $campaign : null
                    );
                }
            }
        }

        Log::info('Leaderboard rebuild completed.', [
            'campaign_id' => $this->campaignId,
            'period' => $this->period,
            'metric' => $this->metric,
            'entries_created' => $totalCreated,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Leaderboard rebuild job failed.', [
            'campaign_id' => $this->campaignId,
            'period' => $this->period,
            'metric' => $this->metric,
            'error' => $exception?->getMessage(),
        ]);
    }
}
