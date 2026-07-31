<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\Player;
use App\Models\PlayerSession;

class ChallengeService
{
    public function __construct(
        protected FriendChallengeEngine $engine,
        protected CampaignHandlerResolver $handlerResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $playerData
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function startChallenge(Campaign $campaign, array $playerData, array $context = []): array
    {
        return $this->engine->startCreatorSession($campaign, $playerData, $context);
    }

    /**
     * @param  array<string, mixed>  $playerData
     * @return array<string, mixed>
     */
    public function joinChallenge(Campaign $campaign, string $token, array $playerData): array
    {
        $link = ChallengeLink::query()
            ->with('creatorSession')
            ->where('campaign_id', $campaign->id)
            ->where(function ($query) use ($token): void {
                $query->where('token', $token);

                if (strlen($token) === 8 && preg_match('/^[A-Z0-9]{8}$/i', $token) === 1) {
                    $query->orWhere('code', strtoupper($token));
                }
            })
            ->firstOrFail();

        return $this->engine->joinChallenge($campaign, $link, $playerData);
    }

    public function getQuestions(Campaign $campaign, PlayerSession $session): array
    {
        return $this->handlerResolver
            ->forType($campaign->type)
            ->bootstrapPlay($campaign, $session);
    }

    /**
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function submitAnswers(Campaign $campaign, PlayerSession $session, array $answers): array
    {
        return $this->engine->submitPlay($campaign, $session, $answers);
    }

    public function finalize(Campaign $campaign, PlayerSession $session): array
    {
        return $this->engine->finalizeSession($campaign, $session);
    }

    public function compare(Campaign $campaign, PlayerSession $creator, PlayerSession $challenger): array
    {
        return $this->engine->compareSessions($campaign, $creator, $challenger);
    }

    public function findSessionByToken(string $token): ?PlayerSession
    {
        return PlayerSession::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function findOrCreatePlayer(array $data): Player
    {
        if (! empty($data['uuid'])) {
            $player = Player::query()->where('uuid', $data['uuid'])->first();

            if ($player) {
                $updates = array_filter([
                    'device' => $data['device'] ?? null,
                    'browser' => $data['browser'] ?? null,
                    'ip' => $data['ip'] ?? null,
                    'country' => $data['country'] ?? null,
                ], fn ($value) => $value !== null && $value !== '');

                if (! empty($data['name'])) {
                    $updates['name'] = $data['name'];
                }

                if ($updates !== []) {
                    $player->update($updates);
                }

                return $player->fresh();
            }
        }

        return Player::query()->create([
            'name' => $data['name'] ?? 'Anonymous',
            'referred_by_player_id' => $data['referred_by_player_id'] ?? null,
            'device' => $data['device'] ?? null,
            'browser' => $data['browser'] ?? null,
            'ip' => $data['ip'] ?? null,
            'country' => $data['country'] ?? null,
        ]);
    }

    public function validateSessionToken(PlayerSession $session, string $token): bool
    {
        return hash_equals($session->token_hash, hash('sha256', $token));
    }

    public function incrementShareCount(ChallengeLink $link): void
    {
        $link->increment('share_count');
        $link->creatorSession?->player?->increment('share_count');
    }
}
