<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JoinChallengeRequest;
use App\Http\Requests\Api\RecordShareRequest;
use App\Http\Requests\Api\RematchRequest;
use App\Http\Requests\Api\SubmitAnswersRequest;
use App\Http\Requests\Api\SubmitPottuPlacementRequest;
use App\Http\Resources\ChallengeLinkResource;
use App\Http\Resources\ChallengeResultResource;
use App\Http\Resources\PlayerSessionResource;
use App\Models\ChallengeLink;
use App\Models\ChallengeResult;
use App\Models\Campaign;
use App\Models\PlayerSession;
use App\Services\ChallengeAnalyticsService;
use App\Services\ChallengeLinkService;
use App\Services\ChallengeService;
use App\Services\FriendChallengeEngine;
use App\Services\RematchService;
use App\Services\ResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class ChallengeController extends Controller
{
    public function __construct(
        protected ChallengeService $challengeService,
        protected ResultService $resultService,
        protected FriendChallengeEngine $engine,
        protected ChallengeAnalyticsService $analyticsService,
        protected ChallengeLinkService $linkService,
        protected RematchService $rematchService,
    ) {}

    public function show(string $token): JsonResponse
    {
        $link = $this->resolveLink($token, ['campaign.theme', 'creatorSession.player', 'friendMedia']);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                ChallengeLinkResource::make($link)->resolve(),
                ['challenge' => $this->engine->buildChallengePreview($link)]
            ),
        ]);
    }

    public function join(JoinChallengeRequest $request, string $token): JsonResponse
    {
        $link = $this->resolveLink($token, ['campaign', 'creatorSession.player']);

        $playerData = [
            'uuid' => $request->validated('player_uuid'),
            'name' => $request->validated('name'),
            'device' => $request->header('X-Device'),
            'browser' => $request->userAgent(),
            'ip' => $request->ip(),
            'country' => $request->header('X-Country'),
        ];

        try {
            $result = $this->challengeService->joinChallenge(
                $link->campaign,
                $token,
                $playerData
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        /** @var PlayerSession $session */
        $session = $result['session']->load(['player', 'campaign', 'challengeLink']);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => PlayerSessionResource::make($session),
                'token' => $result['token'],
                'creator' => $result['creator'],
                'challenge' => $result['challenge'] ?? null,
                'questions' => $result['questions'],
            ],
        ], 201);
    }

    public function submitAnswers(SubmitAnswersRequest $request, string $token, string $uuid): JsonResponse
    {
        $link = $this->resolveLink($token);

        /** @var PlayerSession $session */
        $session = $request->attributes->get('player_session');

        if ((int) $session->challenge_link_id !== (int) $link->id) {
            return response()->json([
                'success' => false,
                'message' => 'Session does not belong to this challenge.',
            ], 403);
        }

        try {
            $result = $this->challengeService->submitAnswers(
                $link->campaign,
                $session,
                $request->normalizedAnswers()
            );
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function submitPottuPlacement(SubmitPottuPlacementRequest $request, string $token, string $uuid): JsonResponse
    {
        $link = $this->resolveLink($token, ['campaign']);

        /** @var PlayerSession $session */
        $session = $request->attributes->get('player_session');

        if ((int) $session->challenge_link_id !== (int) $link->id) {
            return response()->json([
                'success' => false,
                'message' => 'Session does not belong to this challenge.',
            ], 403);
        }

        if ($link->campaign->type !== Campaign::TYPE_POTTU) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available for pottu challenges.',
            ], 422);
        }

        try {
            $result = $this->challengeService->submitAnswers(
                $link->campaign,
                $session,
                $request->toHandlerPayload()
            );
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function recordShare(RecordShareRequest $request, string $token): JsonResponse
    {
        $link = $this->resolveLink($token);

        if (! $this->linkService->canShare($link)) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum shares reached for this challenge.',
            ], 422);
        }

        $player = null;

        if ($request->filled('player_uuid')) {
            $player = $this->challengeService->findOrCreatePlayer([
                'uuid' => $request->validated('player_uuid'),
                'name' => 'Player',
            ]);
        }

        $share = $this->analyticsService->recordShare(
            $link,
            $request->validated('channel'),
            $player
        );

        return response()->json([
            'success' => true,
            'data' => [
                'share_count' => $link->fresh()->share_count,
                'channel' => $share->channel,
            ],
        ]);
    }

    public function rematch(RematchRequest $request, string $token): JsonResponse
    {
        $link = $this->resolveLink($token, ['campaign']);

        $player = $this->challengeService->findOrCreatePlayer([
            'uuid' => $request->validated('player_uuid'),
            'name' => $request->validated('name'),
        ]);

        try {
            $result = $this->rematchService->createRematch(
                $link,
                $player,
                $request->validated('type'),
                array_filter([
                    'friend_name' => $request->validated('friend_name'),
                    'challenge_title' => $request->validated('challenge_title'),
                    'challenge_message' => $request->validated('challenge_message'),
                    'friend_media_id' => $request->validated('friend_media_id'),
                ], fn ($value) => $value !== null && $value !== '')
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        /** @var PlayerSession $session */
        $session = $result['session']->load(['player', 'campaign', 'challengeLink']);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => PlayerSessionResource::make($session),
                'token' => $result['token'],
                'challenge_link' => ChallengeLinkResource::make($result['challenge_link']),
                'questions' => $result['questions'],
            ],
        ], 201);
    }

    public function results(Request $request, string $token): JsonResponse
    {
        $link = $this->resolveLink($token, ['campaign', 'creatorSession.player']);

        $challengerUuid = $request->query('challenger_uuid');

        $creatorSession = $link->creatorSession;

        if (! $creatorSession || ! $creatorSession->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Creator has not completed the challenge yet.',
            ], 422);
        }

        $challengerSessionQuery = PlayerSession::query()
            ->where('challenge_link_id', $link->id)
            ->where('role', 'challenger')
            ->where('status', 'completed');

        if ($challengerUuid) {
            $challengerSessionQuery->where('uuid', $challengerUuid);
        }

        $challengerSession = $challengerSessionQuery
            ->latest('completed_at')
            ->first();

        if (! $challengerSession) {
            return response()->json([
                'success' => false,
                'message' => 'No completed challenger session found for this challenge.',
            ], 404);
        }

        $existingResult = ChallengeResult::query()
            ->with(['badge', 'resultMessage', 'winner', 'creatorSession.player', 'challengerSession.player'])
            ->where('challenge_link_id', $link->id)
            ->where('creator_session_id', $creatorSession->id)
            ->where('challenger_session_id', $challengerSession->id)
            ->first();

        if ($existingResult) {
            return $this->resultResponse(
                $existingResult->load(['badge', 'resultMessage', 'winner', 'creatorSession.player', 'challengerSession.player']),
                $creatorSession,
                $challengerSession
            );
        }

        try {
            $comparison = $this->challengeService->compare(
                $link->campaign,
                $creatorSession,
                $challengerSession
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        /** @var ChallengeResult $result */
        $result = $comparison['result'];

        return $this->resultResponse(
            $result->load(['badge', 'resultMessage', 'winner', 'creatorSession.player', 'challengerSession.player']),
            $creatorSession,
            $challengerSession,
            $comparison['comparison']['details'] ?? null,
            $comparison
        );
    }

    protected function resultResponse(
        ChallengeResult $result,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession,
        ?array $answerDetails = null,
        ?array $comparison = null
    ): JsonResponse {
        $comparison = $comparison ?? [];
        if ($answerDetails === null) {
            $sessionComparison = $this->resultService->compareSessions($creatorSession, $challengerSession);
            $answerDetails = $sessionComparison['details'];
        }

        $meta = is_array($result->meta) ? $result->meta : [];
        $comparison = array_merge([
            'pixel_distance' => $meta['pixel_distance'] ?? null,
            'band' => $meta['band'] ?? null,
            'stars' => $meta['stars'] ?? null,
            'label' => $meta['label'] ?? null,
            'won' => $meta['won'] ?? null,
            'can_create_next_challenge' => $meta['can_create_next_challenge'] ?? false,
            'creator_position' => $meta['creator_position'] ?? null,
            'friend_position' => $meta['friend_position'] ?? null,
            'reference_size' => $meta['reference_size'] ?? null,
        ], $comparison);

        $link = $result->challengeLink ?? $result->load('challengeLink')->challengeLink;
        $friendResults = $link
            ? $this->engine->getAllFriendResults($link, $challengerSession->uuid)
            : ['friends' => [], 'top_winner' => null];

        $accuracyPercent = $result->accuracy !== null
            ? round((float) $result->accuracy, 1)
            : null;

        return response()->json([
            'success' => true,
            'data' => array_merge(
                ChallengeResultResource::make($result)->resolve(),
                [
                    'answer_details' => $answerDetails,
                    'creator_score' => $result->creator_score,
                    'friend_score' => $result->friend_score,
                    'score_diff' => $result->score_diff,
                    'accuracy' => $result->accuracy,
                    'accuracy_percent' => $accuracyPercent,
                    'creator_completion_time_ms' => $result->creator_completion_time_ms,
                    'friend_completion_time_ms' => $result->friend_completion_time_ms,
                    'pixel_distance' => $comparison['pixel_distance'] ?? ($result->meta['details']['pixel_distance'] ?? null),
                    'band' => $comparison['band'] ?? null,
                    'stars' => $comparison['stars'] ?? null,
                    'label' => $comparison['label'] ?? null,
                    'won' => $comparison['won'] ?? null,
                    'can_create_next_challenge' => $comparison['can_create_next_challenge'] ?? false,
                    'creator_position' => $comparison['creator_position'] ?? ($result->meta['details']['creator_position'] ?? null),
                    'friend_position' => $comparison['friend_position'] ?? ($result->meta['details']['friend_position'] ?? null),
                    'reference_size' => $comparison['reference_size'] ?? ($result->meta['details']['reference_size'] ?? null),
                    'all_friends' => $friendResults['friends'],
                    'top_winner' => $friendResults['top_winner'],
                    'challenge' => $this->engine->buildChallengePreview($link),
                ]
            ),
        ]);
    }

    /**
     * @param  array<int, string>  $with
     */
    protected function resolveLink(string $token, array $with = []): ChallengeLink
    {
        $link = $this->linkService->findByPublicToken($token);

        if ($with !== []) {
            $link->loadMissing($with);
        }

        return $link;
    }
}
