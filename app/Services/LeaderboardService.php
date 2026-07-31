<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeResult;
use App\Models\LeaderboardSnapshot;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * @return Collection<int, LeaderboardSnapshot>
     */
    public function getLeaderboard(
        string $period,
        string $metric,
        ?Campaign $campaign = null,
        ?Carbon $date = null,
        int $limit = 50
    ): Collection {
        $date = $date ?? now();

        $snapshots = LeaderboardSnapshot::query()
            ->with('player')
            ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->where('period', $period)
            ->where('metric', $metric)
            ->where('snapshot_date', $date->toDateString())
            ->orderBy('rank')
            ->limit($limit)
            ->get();

        if ($snapshots->isNotEmpty()) {
            return $snapshots;
        }

        return $this->buildLiveLeaderboard($period, $metric, $campaign, $date, $limit);
    }

    /**
     * @return Collection<int, LeaderboardSnapshot>
     */
    protected function buildLiveLeaderboard(
        string $period,
        string $metric,
        ?Campaign $campaign,
        Carbon $date,
        int $limit
    ): Collection {
        $scores = $this->calculateScores($metric, $campaign, $period, $date);

        if ($scores === []) {
            return collect();
        }

        $playerIds = collect($scores)->pluck('player_id')->all();
        $players = Player::query()
            ->whereIn('id', $playerIds)
            ->get()
            ->keyBy('id');

        return collect($scores)
            ->take($limit)
            ->values()
            ->map(function (array $entry, int $index) use ($period, $metric, $campaign, $date, $players) {
                $snapshot = new LeaderboardSnapshot([
                    'campaign_id' => $campaign?->id,
                    'period' => $period,
                    'metric' => $metric,
                    'player_id' => $entry['player_id'],
                    'score' => $entry['score'],
                    'rank' => $index + 1,
                    'snapshot_date' => $date->toDateString(),
                ]);

                $snapshot->setRelation('player', $players->get($entry['player_id']));

                return $snapshot;
            });
    }

    public function generateSnapshot(
        string $period,
        string $metric,
        ?Campaign $campaign = null,
        ?Carbon $date = null
    ): int {
        $date = ($date ?? now())->toDateString();
        $scores = $this->calculateScores($metric, $campaign, $period, Carbon::parse($date));

        return DB::transaction(function () use ($scores, $period, $metric, $campaign, $date) {
            LeaderboardSnapshot::query()
                ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
                ->where('period', $period)
                ->where('metric', $metric)
                ->where('snapshot_date', $date)
                ->delete();

            $rank = 1;
            $created = 0;

            foreach ($scores as $entry) {
                LeaderboardSnapshot::query()->create([
                    'campaign_id' => $campaign?->id,
                    'period' => $period,
                    'metric' => $metric,
                    'player_id' => $entry['player_id'],
                    'score' => $entry['score'],
                    'rank' => $rank++,
                    'snapshot_date' => $date,
                ]);

                $created++;
            }

            return $created;
        });
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function calculateScores(
        string $metric,
        ?Campaign $campaign,
        string $period,
        Carbon $date
    ): array {
        [$start, $end] = $this->periodRange($period, $date);

        return match ($metric) {
            'most_shared' => $this->mostSharedScores($campaign, $start, $end),
            'most_invites' => $this->mostInvitesScores($campaign, $start, $end),
            'highest_match' => $this->highestMatchScores($campaign, $start, $end),
            'most_created' => $this->mostCreatedScores($campaign, $start, $end),
            'most_won' => $this->mostWonScores($campaign, $start, $end),
            'highest_accuracy' => $this->highestAccuracyScores($campaign, $start, $end),
            'longest_chain' => $this->longestChainScores($campaign, $start, $end),
            default => [],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function periodRange(string $period, Carbon $date): array
    {
        return match ($period) {
            'daily' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'weekly' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'monthly' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            'overall' => [Carbon::parse('2020-01-01'), $date->copy()->endOfDay()],
            default => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
        };
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function mostSharedScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $query = Player::query()
            ->select('players.id as player_id', DB::raw('players.share_count as score'))
            ->where('share_count', '>', 0)
            ->orderByDesc('share_count')
            ->limit(100);

        if ($campaign) {
            $query->whereHas('sessions', fn ($q) => $q
                ->where('campaign_id', $campaign->id)
                ->whereBetween('created_at', [$start, $end]));
        }

        return $query->get()->map(fn ($row) => [
            'player_id' => (int) $row->player_id,
            'score' => (int) $row->score,
        ])->all();
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function mostInvitesScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $query = DB::table('referrals')
            ->select('referrer_player_id as player_id', DB::raw('COUNT(*) as score'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('referrer_player_id')
            ->orderByDesc('score')
            ->limit(100);

        if ($campaign) {
            $query->where('campaign_id', $campaign->id);
        }

        return collect($query->get())->map(fn ($row) => [
            'player_id' => (int) $row->player_id,
            'score' => (int) $row->score,
        ])->all();
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function highestMatchScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $query = DB::table('challenge_results')
            ->join('player_sessions as challenger_sessions', 'challenge_results.challenger_session_id', '=', 'challenger_sessions.id')
            ->select('challenger_sessions.player_id', DB::raw('MAX(challenge_results.match_percent) as score'))
            ->whereBetween('challenge_results.created_at', [$start, $end])
            ->groupBy('challenger_sessions.player_id')
            ->orderByDesc('score')
            ->limit(100);

        if ($campaign) {
            $query->join('challenge_links', 'challenge_results.challenge_link_id', '=', 'challenge_links.id')
                ->where('challenge_links.campaign_id', $campaign->id);
        }

        return collect($query->get())->map(fn ($row) => [
            'player_id' => (int) $row->player_id,
            'score' => (int) round((float) $row->score),
        ])->all();
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function mostCreatedScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $query = DB::table('challenge_links')
            ->join('player_sessions', 'challenge_links.creator_session_id', '=', 'player_sessions.id')
            ->select('player_sessions.player_id', DB::raw('COUNT(*) as score'))
            ->whereBetween('challenge_links.created_at', [$start, $end])
            ->groupBy('player_sessions.player_id')
            ->orderByDesc('score')
            ->limit(100);

        if ($campaign) {
            $query->where('challenge_links.campaign_id', $campaign->id);
        }

        return collect($query->get())->map(fn ($row) => [
            'player_id' => (int) $row->player_id,
            'score' => (int) $row->score,
        ])->all();
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function mostWonScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $query = DB::table('challenge_results')
            ->join('player_sessions as challenger_sessions', 'challenge_results.challenger_session_id', '=', 'challenger_sessions.id')
            ->select('challenger_sessions.player_id', DB::raw('COUNT(*) as score'))
            ->whereColumn('challenge_results.winner_player_id', 'challenger_sessions.player_id')
            ->whereBetween('challenge_results.created_at', [$start, $end])
            ->groupBy('challenger_sessions.player_id')
            ->orderByDesc('score')
            ->limit(100);

        if ($campaign) {
            $query->join('challenge_links', 'challenge_results.challenge_link_id', '=', 'challenge_links.id')
                ->where('challenge_links.campaign_id', $campaign->id);
        }

        return collect($query->get())->map(fn ($row) => [
            'player_id' => (int) $row->player_id,
            'score' => (int) $row->score,
        ])->all();
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function highestAccuracyScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $query = DB::table('challenge_results')
            ->join('player_sessions as challenger_sessions', 'challenge_results.challenger_session_id', '=', 'challenger_sessions.id')
            ->select('challenger_sessions.player_id', DB::raw('MAX(challenge_results.accuracy) as score'))
            ->whereNotNull('challenge_results.accuracy')
            ->whereBetween('challenge_results.created_at', [$start, $end])
            ->groupBy('challenger_sessions.player_id')
            ->orderByDesc('score')
            ->limit(100);

        if ($campaign) {
            $query->join('challenge_links', 'challenge_results.challenge_link_id', '=', 'challenge_links.id')
                ->where('challenge_links.campaign_id', $campaign->id);
        }

        return collect($query->get())->map(fn ($row) => [
            'player_id' => (int) $row->player_id,
            'score' => (int) round((float) $row->score),
        ])->all();
    }

    /**
     * @return array<int, array{player_id: int, score: int}>
     */
    protected function longestChainScores(?Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $links = DB::table('challenge_links')
            ->select('id', 'parent_link_id', 'creator_session_id', 'created_at')
            ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->whereBetween('created_at', [$start, $end])
            ->get();

        if ($links->isEmpty()) {
            return [];
        }

        $sessionPlayers = DB::table('player_sessions')
            ->whereIn('id', $links->pluck('creator_session_id'))
            ->pluck('player_id', 'id');

        $children = [];
        foreach ($links as $link) {
            if ($link->parent_link_id) {
                $children[$link->parent_link_id][] = $link->id;
            }
        }

        $depths = [];
        $computeDepth = function (int $linkId) use (&$computeDepth, &$children, $links): int {
            $childIds = $children[$linkId] ?? [];
            if ($childIds === []) {
                return 1;
            }

            $max = 1;
            foreach ($childIds as $childId) {
                $max = max($max, 1 + $computeDepth($childId));
            }

            return $max;
        };

        foreach ($links as $link) {
            $playerId = $sessionPlayers[$link->creator_session_id] ?? null;
            if (! $playerId) {
                continue;
            }

            $depth = $computeDepth((int) $link->id);
            $depths[$playerId] = max($depths[$playerId] ?? 0, $depth);
        }

        arsort($depths);

        return collect($depths)
            ->take(100)
            ->map(fn ($score, $playerId) => [
                'player_id' => (int) $playerId,
                'score' => (int) $score,
            ])
            ->values()
            ->all();
    }

    public function getPlayerRank(
        Player $player,
        string $period,
        string $metric,
        ?Campaign $campaign = null,
        ?Carbon $date = null
    ): ?LeaderboardSnapshot {
        $date = $date ?? now()->toDateString();

        return LeaderboardSnapshot::query()
            ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->where('period', $period)
            ->where('metric', $metric)
            ->where('snapshot_date', $date)
            ->where('player_id', $player->id)
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentComparisons(
        ?Campaign $campaign,
        string $period,
        ?Carbon $date = null,
        int $limit = 15
    ): array {
        if (! $campaign) {
            return [];
        }

        [$start, $end] = $this->periodRange($period, $date ?? now());

        return ChallengeResult::query()
            ->with([
                'creatorSession.player',
                'challengerSession.player',
                'winner',
                'challengeLink',
            ])
            ->whereHas('challengeLink', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->whereBetween('challenge_results.created_at', [$start, $end])
            ->latest('challenge_results.created_at')
            ->limit($limit)
            ->get()
            ->map(function (ChallengeResult $result) use ($campaign) {
                $meta = is_array($result->meta) ? $result->meta : [];
                $creator = $result->creatorSession?->player;
                $challenger = $result->challengerSession?->player;
                $winner = $result->winner;

                $payload = [
                    'uuid' => $result->uuid,
                    'challenge_token' => $result->challengeLink?->ensureShareToken(),
                    'creator' => $creator ? [
                        'uuid' => $creator->uuid,
                        'name' => $creator->name,
                    ] : null,
                    'challenger' => $challenger ? [
                        'uuid' => $challenger->uuid,
                        'name' => $challenger->name,
                    ] : null,
                    'winner' => $winner ? [
                        'uuid' => $winner->uuid,
                        'name' => $winner->name,
                    ] : null,
                    'accuracy' => $result->accuracy !== null ? round((float) $result->accuracy, 1) : null,
                    'pixel_distance' => isset($meta['pixel_distance'])
                        ? round((float) $meta['pixel_distance'], 1)
                        : ($result->score_diff !== null ? round((float) $result->score_diff, 1) : null),
                    'label' => $meta['label'] ?? null,
                    'match_percent' => $result->match_percent !== null ? round((float) $result->match_percent, 1) : null,
                    'created_at' => $result->created_at?->toIso8601String(),
                ];

                if ($campaign->type === Campaign::TYPE_POTTU) {
                    $payload['creator_accuracy'] = 100;
                    $payload['challenger_accuracy'] = $payload['accuracy'];
                    $payload['higher_accuracy_player'] = $winner?->name;
                } else {
                    $payload['creator_score'] = $result->creator_score !== null
                        ? round((float) $result->creator_score, 1)
                        : null;
                    $payload['challenger_score'] = $result->friend_score !== null
                        ? round((float) $result->friend_score, 1)
                        : ($payload['match_percent'] ?? null);
                }

                return $payload;
            })
            ->values()
            ->all();
    }

    public function defaultMetricForCampaign(?Campaign $campaign): string
    {
        if ($campaign?->type === Campaign::TYPE_POTTU) {
            return 'highest_accuracy';
        }

        return 'highest_match';
    }

    /**
     * @return array<int, string>
     */
    public function availableMetricsForCampaign(?Campaign $campaign): array
    {
        if ($campaign?->type === Campaign::TYPE_POTTU) {
            return ['highest_accuracy', 'most_won', 'most_created', 'longest_chain'];
        }

        return ['highest_match', 'most_shared', 'most_invites'];
    }
}
