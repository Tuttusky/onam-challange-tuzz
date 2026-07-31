<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerController extends Controller
{
    public function index(Request $request): View
    {
        $players = Player::query()
            ->withCount('sessions')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('referral_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('country'), fn ($q) => $q->where('country', $request->country))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $countries = Player::query()
            ->whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return view('admin.players.index', compact('players', 'countries'));
    }

    public function show(Player $player): View
    {
        $player->load([
            'sessions.campaign',
            'referrer',
            'referrals',
            'wonResults.challengeLink.campaign',
        ]);

        return view('admin.players.show', compact('player'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'players-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'UUID', 'Name', 'Referral Code', 'Country', 'Device', 'Browser', 'Share Count', 'Created At']);

            Player::query()
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orderBy('id')
                ->chunk(200, function ($players) use ($handle) {
                    foreach ($players as $player) {
                        fputcsv($handle, [
                            $player->id,
                            $player->uuid,
                            $player->name,
                            $player->referral_code,
                            $player->country,
                            $player->device,
                            $player->browser,
                            $player->share_count,
                            $player->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
