<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardResource;
use App\Models\Campaign;
use App\Services\LeaderboardService;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request, string $period = 'daily'): JsonResponse
    {
        if (! WebsiteSettingsService::isFeatureEnabled('leaderboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Leaderboard is currently disabled.',
            ], 403);
        }

        $validated = $request->validate([
            'metric' => ['nullable', 'string', 'in:most_shared,most_invites,highest_match,most_created,most_won,highest_accuracy,longest_chain'],
            'campaign_slug' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $periods = ['daily', 'weekly', 'monthly', 'overall'];

        if (! in_array($period, $periods, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid leaderboard period.',
            ], 422);
        }

        $campaign = null;

        if (! empty($validated['campaign_slug'])) {
            $campaign = Campaign::query()
                ->where('slug', $validated['campaign_slug'])
                ->firstOrFail();
        }

        $metric = $validated['metric'] ?? $this->leaderboardService->defaultMetricForCampaign($campaign);
        $limit = (int) ($validated['limit'] ?? 50);

        $availableMetrics = $this->leaderboardService->availableMetricsForCampaign($campaign);
        if (! in_array($metric, $availableMetrics, true)) {
            $metric = $this->leaderboardService->defaultMetricForCampaign($campaign);
        }

        $entries = $this->leaderboardService->getLeaderboard(
            $period,
            $metric,
            $campaign,
            null,
            $limit
        );

        $resolvedEntries = LeaderboardResource::collection($entries)->resolve();
        $topWinner = $resolvedEntries[0] ?? null;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'metric' => $metric,
                'campaign_slug' => $campaign?->slug,
                'campaign_type' => $campaign?->type,
                'campaign_name' => $campaign?->name,
                'available_metrics' => $availableMetrics,
                'top_winner' => $topWinner,
                'entries' => $resolvedEntries,
                'comparisons' => $this->leaderboardService->getRecentComparisons($campaign, $period, null, 15),
            ],
        ]);
    }
}
