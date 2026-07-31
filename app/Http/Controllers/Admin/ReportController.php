<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ChallengeResult;
use App\Models\PlayerSession;
use App\Models\VisitEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->input('days', 30);
        $since = now()->subDays($days);
        $campaignId = $request->input('campaign_id');

        $visitorQuery = VisitEvent::query()
            ->where('created_at', '>=', $since)
            ->when($campaignId, fn ($q) => $q->where('campaign_id', $campaignId));

        $visitors = (clone $visitorQuery)->distinct('ip')->count('ip');
        $totalEvents = (clone $visitorQuery)->count();

        $devices = (clone $visitorQuery)
            ->select('device', DB::raw('COUNT(*) as total'))
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $browsers = (clone $visitorQuery)
            ->select('browser', DB::raw('COUNT(*) as total'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $countries = (clone $visitorQuery)
            ->select('country', DB::raw('COUNT(*) as total'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $sessionsQuery = PlayerSession::query()
            ->where('created_at', '>=', $since)
            ->when($campaignId, fn ($q) => $q->where('campaign_id', $campaignId));

        $started = (clone $sessionsQuery)->count();
        $completed = (clone $sessionsQuery)->where('status', 'completed')->count();
        $completionRate = $started > 0 ? round(($completed / $started) * 100, 1) : 0;

        $dailyVisitors = VisitEvent::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT ip) as total'))
            ->where('created_at', '>=', $since)
            ->when($campaignId, fn ($q) => $q->where('campaign_id', $campaignId))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.reports.index', compact(
            'visitors',
            'totalEvents',
            'devices',
            'browsers',
            'countries',
            'started',
            'completed',
            'completionRate',
            'dailyVisitors',
            'campaigns',
            'days',
            'campaignId'
        ));
    }
}
