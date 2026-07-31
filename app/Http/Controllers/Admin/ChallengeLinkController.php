<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChallengeLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChallengeLinkController extends Controller
{
    public function index(Request $request): View
    {
        $links = ChallengeLink::query()
            ->with(['campaign', 'creatorSession.player', 'pottuImage'])
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.$request->search.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.challenge-links.index', compact('links'));
    }

    public function show(ChallengeLink $challengeLink): View
    {
        $challengeLink->load([
            'campaign',
            'creatorSession.player',
            'pottuImage',
            'results.challengerSession.player',
            'results.winner',
        ]);

        return view('admin.challenge-links.show', compact('challengeLink'));
    }
}
