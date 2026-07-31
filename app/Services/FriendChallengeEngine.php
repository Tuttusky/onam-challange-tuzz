<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\ChallengeResult;
use App\Models\CouponEntry;
use App\Models\GameScore;
use App\Models\LuckyDrawEntry;
use App\Models\PlayerSession;
use App\Domains\Campaign\DTO\ScorePayload;
use App\Services\Pottu\PottuCustomImageService;
use App\Services\Pottu\PottuSettingsService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FriendChallengeEngine
{
    public function __construct(
        protected CampaignHandlerResolver $handlerResolver,
        protected ChallengeLinkService $linkService,
        protected ScoreComparisonService $comparisonService,
        protected FriendMediaService $mediaService,
        protected PottuCustomImageService $pottuCustomImageService,
    ) {}

    /**
     * @param  array<string, mixed>  $playerData
     * @param  array<string, mixed>  $personalization
     * @return array<string, mixed>
     */
    public function startCreatorSession(Campaign $campaign, array $playerData, array $personalization = []): array
    {
        if (! $campaign->isActive()) {
            throw new RuntimeException('Campaign is not active.');
        }

        $player = app(ChallengeService::class)->findOrCreatePlayer($playerData);
        $token = \Illuminate\Support\Str::random(64);

        return DB::transaction(function () use ($campaign, $player, $token, $personalization) {
            $session = PlayerSession::query()->create([
                'campaign_id' => $campaign->id,
                'player_id' => $player->id,
                'role' => 'creator',
                'status' => 'started',
                'token_hash' => hash('sha256', $token),
                'started_at' => now(),
            ]);

            $friendName = $personalization['friend_name'] ?? 'Friend';
            $title = $this->linkService->buildTitle(
                $campaign,
                $friendName,
                $personalization['challenge_title'] ?? null
            );

            $link = ChallengeLink::query()->create([
                'campaign_id' => $campaign->id,
                'creator_session_id' => $session->id,
                'friend_name' => $friendName,
                'challenge_title' => $title,
                'challenge_message' => $personalization['challenge_message'] ?? null,
                'friend_media_id' => $personalization['friend_media_id'] ?? null,
                'parent_link_id' => $personalization['parent_link_id'] ?? null,
                'max_rematches' => (int) WebsiteSettingsService::getFriendChallengeSetting('max_rematches', 10),
                'is_active' => true,
                'is_finalized' => false,
                'expires_at' => $this->linkService->resolveExpiry(),
            ]);

            $session->update(['challenge_link_id' => $link->id]);

            $handler = $this->handlerResolver->forType($campaign->type);
            $questions = $handler->bootstrapPlay($campaign, $session->fresh());

            return [
                'session' => $session->fresh(['campaign.theme', 'challengeLink.friendMedia']),
                'token' => $token,
                'challenge_link' => $link->fresh(['friendMedia']),
                'questions' => $questions,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $playerData
     * @return array<string, mixed>
     */
    public function joinChallenge(Campaign $campaign, ChallengeLink $link, array $playerData): array
    {
        if (! $link->isUsable()) {
            throw new RuntimeException('Challenge link is expired or inactive.');
        }

        if ($link->pottu_image_id) {
            $customImage = \App\Models\PottuImage::query()->find($link->pottu_image_id);

            if ($customImage?->isCustom()) {
                $this->pottuCustomImageService->assertUsable($customImage);
            }
        }

        if (! $link->is_finalized) {
            if ($link->creatorSession?->isCompleted()) {
                $link->update(['is_finalized' => true]);
            } else {
                throw new RuntimeException('Creator has not completed the challenge yet.');
            }
        }

        if (! $link->canAcceptMoreChallengers($campaign)) {
            throw new RuntimeException('This challenge has reached the maximum number of friends.');
        }

        $player = app(ChallengeService::class)->findOrCreatePlayer($playerData);

        if ($link->creatorSession->player_id === $player->id) {
            throw new RuntimeException('You cannot challenge yourself.');
        }

        $token = \Illuminate\Support\Str::random(64);

        $session = DB::transaction(function () use ($campaign, $player, $link, $token) {
            return PlayerSession::query()->create([
                'campaign_id' => $campaign->id,
                'player_id' => $player->id,
                'role' => 'challenger',
                'status' => 'started',
                'challenge_link_id' => $link->id,
                'parent_session_id' => $link->creator_session_id,
                'token_hash' => hash('sha256', $token),
                'started_at' => now(),
            ]);
        });

        $handler = $this->handlerResolver->forType($campaign->type);
        $questions = $handler->bootstrapPlay($campaign, $session);

        return [
            'session' => $session->load(['player', 'campaign', 'challengeLink']),
            'token' => $token,
            'creator' => $link->creatorSession->player->only(['uuid', 'name']),
            'challenge' => $this->buildChallengePreview($link),
            'questions' => $questions,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function submitPlay(Campaign $campaign, PlayerSession $session, array $answers): array
    {
        $handler = $this->handlerResolver->forType($campaign->type);

        return $handler->submitPlay($campaign, $session, $answers);
    }

    /**
     * @return array<string, mixed>
     */
    public function finalizeSession(Campaign $campaign, PlayerSession $session): array
    {
        $handler = $this->handlerResolver->forType($campaign->type);
        $payload = $handler->finalizePlay($campaign, $session);

        $this->storeGameScore($session, $payload);

        $link = $session->createdChallengeLink ?? $session->challengeLink;
        $response = [
            'session_id' => $session->uuid,
            'status' => $session->fresh()->status,
            'score' => $payload->toArray(),
            'challenge_token' => $link?->ensureShareToken(),
            'share_message' => $campaign->share_message,
        ];

        if ($session->role === 'creator' && $link) {
            $link->update([
                'creator_score' => $payload->score,
                'creator_completion_time_ms' => $payload->completionTimeMs,
                'is_finalized' => true,
            ]);

            $response['challenge_token'] = $link->ensureShareToken();
            $response['challenge_link'] = $this->buildChallengePreview($link->fresh(['friendMedia', 'creatorSession.player']));
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function compareSessions(
        Campaign $campaign,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession
    ): array {
        if (! $creatorSession->isCompleted() || ! $challengerSession->isCompleted()) {
            throw new RuntimeException('Both sessions must be completed before comparing results.');
        }

        $link = $creatorSession->createdChallengeLink ?? $creatorSession->challengeLink;

        if (! $link) {
            throw new RuntimeException('Challenge link not found for creator session.');
        }

        $handler = $this->handlerResolver->forType($campaign->type);
        $challengerPayload = $handler->comparePlay($campaign, $creatorSession, $challengerSession);

        $creatorGameScore = $creatorSession->gameScore;
        $creatorPayload = $creatorGameScore
            ? new ScorePayload(
                score: (float) $creatorGameScore->score,
                completionTimeMs: (int) $creatorGameScore->completion_time_ms,
                accuracy: $creatorGameScore->accuracy !== null ? (float) $creatorGameScore->accuracy : null,
                achievements: $creatorGameScore->achievements ?? [],
                meta: $creatorGameScore->meta ?? [],
            )
            : new ScorePayload(
                score: (float) ($link->creator_score ?? 0),
                completionTimeMs: (int) ($link->creator_completion_time_ms ?? 0),
            );

        $this->storeGameScore($challengerSession, $challengerPayload);

        $comparison = $this->comparisonService->compare(
            $campaign,
            $creatorSession,
            $challengerSession,
            $creatorPayload,
            $challengerPayload
        );

        $badge = $comparison['badge'] ?? null;
        $resultMessage = $comparison['result_message'] ?? null;

        $result = ChallengeResult::query()->updateOrCreate(
            [
                'challenge_link_id' => $link->id,
                'creator_session_id' => $creatorSession->id,
                'challenger_session_id' => $challengerSession->id,
            ],
            [
                'match_count' => $comparison['match_count'],
                'total_questions' => $comparison['total_questions'],
                'match_percent' => $comparison['match_percent'],
                'creator_score' => $comparison['creator_score'],
                'friend_score' => $comparison['friend_score'],
                'score_diff' => $comparison['score_diff'],
                'accuracy' => $comparison['accuracy'],
                'creator_completion_time_ms' => $creatorPayload->completionTimeMs,
                'friend_completion_time_ms' => $challengerPayload->completionTimeMs,
                'winner_player_id' => $comparison['winner_player_id'],
                'badge_id' => is_object($badge) ? $badge->id : ($badge['id'] ?? null),
                'result_message_id' => is_object($resultMessage) ? $resultMessage->id : ($resultMessage['id'] ?? null),
                'meta' => [
                    'details' => $comparison['details'],
                    'creator_achievements' => $creatorPayload->achievements,
                    'friend_achievements' => $challengerPayload->achievements,
                    'pixel_distance' => $comparison['pixel_distance'] ?? null,
                    'band' => $comparison['band'] ?? null,
                    'label' => $comparison['label'] ?? null,
                    'won' => $comparison['won'] ?? null,
                    'can_create_next_challenge' => $comparison['can_create_next_challenge'] ?? false,
                    'creator_position' => $comparison['creator_position'] ?? null,
                    'friend_position' => $comparison['friend_position'] ?? null,
                    'reference_size' => $comparison['reference_size'] ?? null,
                ],
            ]
        );

        $this->createRewardEntries($result, $creatorSession, $challengerSession, $campaign, $comparison);

        return [
            'result' => $result->load(['badge', 'resultMessage', 'winner']),
            'comparison' => $comparison,
            'creator' => $creatorSession->player->only(['uuid', 'name']),
            'challenger' => $challengerSession->player->only(['uuid', 'name']),
            'creator_score' => $comparison['creator_score'],
            'friend_score' => $comparison['friend_score'],
            'creator_completion_time_ms' => $creatorPayload->completionTimeMs,
            'friend_completion_time_ms' => $challengerPayload->completionTimeMs,
            'can_create_next_challenge' => $comparison['can_create_next_challenge'] ?? false,
        ];
    }

    /**
     * @return array{friends: array<int, array<string, mixed>>, top_winner: ?array<string, mixed>}
     */
    public function getAllFriendResults(ChallengeLink $link, ?string $highlightChallengerUuid = null): array
    {
        $results = ChallengeResult::query()
            ->with(['challengerSession.player', 'winner'])
            ->where('challenge_link_id', $link->id)
            ->orderByDesc('accuracy')
            ->orderBy('score_diff')
            ->get();

        $friends = $results->map(function (ChallengeResult $result) use ($highlightChallengerUuid) {
            $meta = is_array($result->meta) ? $result->meta : [];
            $challenger = $result->challengerSession?->player;
            $accuracy = $result->accuracy !== null ? round((float) $result->accuracy, 1) : null;

            return [
                'uuid' => $challenger?->uuid,
                'session_uuid' => $result->challengerSession?->uuid,
                'name' => $challenger?->name ?? 'Friend',
                'position' => $meta['friend_position'] ?? ($meta['details']['friend_position'] ?? null),
                'accuracy' => $accuracy,
                'accuracy_percent' => $accuracy,
                'pixel_distance' => isset($meta['pixel_distance'])
                    ? round((float) $meta['pixel_distance'], 1)
                    : ($result->score_diff !== null ? round((float) $result->score_diff, 1) : null),
                'label' => $meta['label'] ?? null,
                'won' => (bool) ($meta['won'] ?? false),
                'is_winner' => $result->winner_player_id === $result->challengerSession?->player_id,
                'is_current' => $highlightChallengerUuid
                    && $result->challengerSession?->uuid === $highlightChallengerUuid,
                'result_uuid' => $result->uuid,
            ];
        })->filter(fn (array $friend) => $friend['position'] !== null)->values()->all();

        $topWinner = null;

        if ($friends !== []) {
            $best = collect($friends)->sortByDesc('accuracy')->first();
            $topWinner = [
                'uuid' => $best['uuid'],
                'name' => $best['name'],
                'accuracy' => $best['accuracy'],
                'pixel_distance' => $best['pixel_distance'],
                'label' => $best['label'],
            ];
        }

        return [
            'friends' => $friends,
            'top_winner' => $topWinner,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildChallengePreview(ChallengeLink $link): array
    {
        $link->loadMissing(['friendMedia', 'creatorSession.player', 'campaign']);

        return [
            'id' => $link->id,
            'code' => $link->code,
            'token' => $link->ensureShareToken(),
            'friend_name' => $link->friend_name,
            'challenge_title' => $link->challenge_title,
            'challenge_message' => $link->challenge_message,
            'creator_score' => $link->creator_score,
            'creator_completion_time_ms' => $link->creator_completion_time_ms,
            'creator' => [
                'uuid' => $link->creatorSession?->player?->uuid,
                'name' => $link->creatorSession?->player?->name ?? 'Player',
            ],
            'friend_media' => $this->mediaService->toPublicArray($link->friendMedia),
            'share_count' => $link->share_count,
            'is_usable' => $link->isUsable(),
            'is_finalized' => $link->is_finalized,
            'expires_at' => $link->expires_at?->toIso8601String(),
            'campaign' => $link->campaign ? [
                'slug' => $link->campaign->slug,
                'name' => $link->campaign->name,
                'type' => $link->campaign->type,
            ] : null,
        ];
    }

    protected function storeGameScore(PlayerSession $session, ScorePayload $payload): GameScore
    {
        return GameScore::query()->updateOrCreate(
            ['player_session_id' => $session->id],
            [
                'score' => $payload->score,
                'completion_time_ms' => $payload->completionTimeMs,
                'accuracy' => $payload->accuracy,
                'achievements' => $payload->achievements,
                'meta' => $payload->meta,
            ]
        );
    }

    protected function createRewardEntries(
        ChallengeResult $result,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession,
        Campaign $campaign,
        array $comparison = []
    ): void {
        if ($campaign->type === Campaign::TYPE_POTTU) {
            $settings = PottuSettingsService::forCampaign($campaign);
            $rewards = $settings['rewards'] ?? [];
            $won = (bool) ($comparison['won'] ?? false);

            if (! $won) {
                return;
            }

            $playerIds = [];

            if (! empty($rewards['coupon']) || ! empty($settings['coupon_enabled'])) {
                $playerIds[] = $challengerSession->player_id;
            }

            if (! empty($rewards['lucky_draw'])) {
                $playerIds[] = $challengerSession->player_id;
            }

            $playerIds = array_unique($playerIds);

            foreach ($playerIds as $playerId) {
                if (! empty($rewards['coupon']) || ! empty($settings['coupon_enabled'])) {
                    CouponEntry::query()->firstOrCreate([
                        'challenge_result_id' => $result->id,
                        'player_id' => $playerId,
                    ], ['status' => 'pending']);
                }

                if (! empty($rewards['lucky_draw'])) {
                    LuckyDrawEntry::query()->firstOrCreate([
                        'challenge_result_id' => $result->id,
                        'player_id' => $playerId,
                    ], ['status' => 'pending']);
                }
            }

            return;
        }

        foreach ([$creatorSession->player_id, $challengerSession->player_id] as $playerId) {
            CouponEntry::query()->firstOrCreate([
                'challenge_result_id' => $result->id,
                'player_id' => $playerId,
            ], ['status' => 'pending']);

            LuckyDrawEntry::query()->firstOrCreate([
                'challenge_result_id' => $result->id,
                'player_id' => $playerId,
            ], ['status' => 'pending']);
        }
    }
}
