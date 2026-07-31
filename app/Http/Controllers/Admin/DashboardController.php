<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\ChallengeResult;
use App\Models\LeaderboardSnapshot;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();

        $stats = [
            'total_players' => Player::query()->count(),
            'today_players' => Player::query()->where('created_at', '>=', $today)->count(),
            'active_challenges' => ChallengeLink::query()->where('is_active', true)->count(),
            'completed_challenges' => PlayerSession::query()->where('status', 'completed')->count(),
            'share_count' => (int) Player::query()->sum('share_count'),
            'total_campaigns' => Campaign::query()->count(),
            'active_campaigns' => Campaign::query()->where('status', 'active')->count(),
        ];

        $topPlayers = Player::query()
            ->orderByDesc('share_count')
            ->limit(10)
            ->get(['id', 'name', 'referral_code', 'share_count', 'created_at']);

        $chartLabels = collect(range(6, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->format('M d'))->values();

        $playersChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();
            $end = $date->copy()->endOfDay();

            return Player::query()->whereBetween('created_at', [$date, $end])->count();
        })->values();

        $completionsChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();
            $end = $date->copy()->endOfDay();

            return PlayerSession::query()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$date, $end])
                ->count();
        })->values();

        $recentResults = ChallengeResult::query()
            ->with(['challengeLink.campaign', 'winner'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'stats',
            'topPlayers',
            'chartLabels',
            'playersChart',
            'completionsChart',
            'recentResults'
        ));
    }
}
