<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\LeaderboardSnapshot;
use App\Services\LeaderboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request): View
    {
        $period = $request->input('period', 'daily');
        $metric = $request->input('metric', 'most_shared');
        $campaignId = $request->input('campaign_id');
        $campaign = $campaignId ? Campaign::query()->find($campaignId) : null;

        $entries = $this->leaderboardService->getLeaderboard(
            $period,
            $metric,
            $campaign,
            null,
            50
        );

        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        $lastSnapshot = LeaderboardSnapshot::query()
            ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->where('period', $period)
            ->where('metric', $metric)
            ->max('created_at');

        return view('admin.leaderboards.index', compact(
            'entries',
            'campaigns',
            'period',
            'metric',
            'campaign',
            'lastSnapshot'
        ));
    }

    public function rebuild(Request $request): RedirectResponse
    {
        $request->validate([
            'period' => ['required', 'in:daily,weekly,monthly,overall'],
            'metric' => ['required', 'in:most_shared,most_invites,highest_match,most_created,most_won,highest_accuracy,longest_chain'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
        ]);

        $campaign = $request->filled('campaign_id')
            ? Campaign::query()->find($request->campaign_id)
            : null;

        $count = $this->leaderboardService->generateSnapshot(
            $request->input('period'),
            $request->input('metric'),
            $campaign
        );

        return back()->with('success', "Leaderboard rebuilt with {$count} entries.");
    }
}
