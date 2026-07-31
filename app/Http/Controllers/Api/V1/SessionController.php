<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StartSessionRequest;
use App\Http\Requests\Api\SubmitAnswersRequest;
use App\Http\Requests\Api\SubmitPottuPlacementRequest;
use App\Http\Resources\ChallengeLinkResource;
use App\Http\Resources\PlayerSessionResource;
use App\Http\Resources\QuestionResource;
use App\Models\Campaign;
use App\Models\PlayerSession;
use App\Services\ChallengeService;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class SessionController extends Controller
{
    public function __construct(
        protected ChallengeService $challengeService,
        protected ReferralService $referralService
    ) {}

    public function start(StartSessionRequest $request): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('slug', $request->validated('campaign_slug'))
            ->firstOrFail();

        $playerData = [
            'uuid' => $request->validated('player_uuid'),
            'name' => $request->validated('name'),
            'device' => $request->header('X-Device'),
            'browser' => $request->userAgent(),
            'ip' => $request->ip(),
            'country' => $request->header('X-Country'),
        ];

        $player = $this->challengeService->findOrCreatePlayer($playerData);

        if ($request->filled('referral_code')) {
            try {
                $this->referralService->applyReferral(
                    $player,
                    $request->validated('referral_code'),
                    $campaign
                );
            } catch (InvalidArgumentException) {
                // Referral is optional; invalid codes should not block session start.
            }
        }

        try {
            $personalization = array_filter([
                'friend_name' => $request->validated('friend_name'),
                'challenge_title' => $request->validated('challenge_title'),
                'challenge_message' => $request->validated('challenge_message'),
                'friend_media_id' => $request->validated('friend_media_id'),
                'parent_link_id' => $request->validated('parent_link_id'),
            ], fn ($value) => $value !== null && $value !== '');

            $result = $this->challengeService->startChallenge($campaign, [
                'uuid' => $player->uuid,
                'name' => $player->name,
                'device' => $request->header('X-Device'),
                'browser' => $request->userAgent(),
                'ip' => $request->ip(),
                'country' => $request->header('X-Country'),
            ], $personalization);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        /** @var PlayerSession $session */
        $session = $result['session']->load(['player', 'campaign.theme', 'challengeLink']);

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

    public function questions(Request $request, string $uuid): JsonResponse
    {
        /** @var PlayerSession $session */
        $session = $request->attributes->get('player_session');
        $campaign = $session->campaign;

        $questions = $this->challengeService->getQuestions($campaign, $session);

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    public function submitAnswers(SubmitAnswersRequest $request, string $uuid): JsonResponse
    {
        /** @var PlayerSession $session */
        $session = $request->attributes->get('player_session');
        $campaign = $session->campaign;

        try {
            $result = $this->challengeService->submitAnswers(
                $campaign,
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

    public function submitPottuPlacement(SubmitPottuPlacementRequest $request, string $uuid): JsonResponse
    {
        /** @var PlayerSession $session */
        $session = $request->attributes->get('player_session');
        $campaign = $session->campaign;

        if ($campaign->type !== Campaign::TYPE_POTTU) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available for pottu challenges.',
            ], 422);
        }

        try {
            $result = $this->challengeService->submitAnswers(
                $campaign,
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

    public function finalize(Request $request, string $uuid): JsonResponse
    {
        /** @var PlayerSession $session */
        $session = $request->attributes->get('player_session');
        $campaign = $session->campaign;

        try {
            $result = $this->challengeService->finalize($campaign, $session);
        } catch (RuntimeException $exception) {
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
}
