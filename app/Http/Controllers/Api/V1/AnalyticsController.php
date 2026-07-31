<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VisitEventRequest;
use App\Jobs\AnalyticsIngestJob;
use App\Models\Campaign;
use App\Models\Player;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    public function trackVisit(VisitEventRequest $request): JsonResponse
    {
        if (! $this->analyticsService->isTrackingEnabled()) {
            return response()->json([
                'success' => true,
                'message' => 'Analytics tracking is disabled.',
            ]);
        }

        $campaignId = null;

        if ($request->filled('campaign_slug')) {
            $campaignId = Campaign::query()
                ->where('slug', $request->validated('campaign_slug'))
                ->value('id');
        }

        $playerId = null;

        if ($request->filled('player_uuid')) {
            $playerId = Player::query()
                ->where('uuid', $request->validated('player_uuid'))
                ->value('id');
        }

        AnalyticsIngestJob::dispatch([
            'event_type' => $request->validated('event_type'),
            'campaign_id' => $campaignId,
            'player_id' => $playerId,
            'source' => $request->validated('source'),
            'device' => $request->header('X-Device'),
            'browser' => $request->userAgent(),
            'country' => $request->header('X-Country'),
            'ip' => $request->ip(),
            'meta' => $request->validated('meta'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visit event queued for processing.',
        ], 202);
    }
}
