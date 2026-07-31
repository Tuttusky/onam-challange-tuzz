<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChallengeResult;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChallengeResultController extends Controller
{
    public function index(Request $request): View
    {
        $results = ChallengeResult::query()
            ->with(['challengeLink.campaign', 'challengeLink.pottuImage', 'winner', 'badge'])
            ->when($request->filled('campaign_id'), function ($q) use ($request) {
                $q->whereHas('challengeLink', fn ($link) => $link->where('campaign_id', $request->campaign_id));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.challenge-results.index', compact('results'));
    }

    public function show(ChallengeResult $challengeResult): View
    {
        $challengeResult->load([
            'challengeLink.campaign',
            'challengeLink.pottuImage',
            'creatorSession.player',
            'challengerSession.player',
            'winner',
            'badge',
            'resultMessage',
        ]);

        return view('admin.challenge-results.show', compact('challengeResult'));
    }
}
