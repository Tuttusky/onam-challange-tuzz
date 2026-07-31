<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChallengeLink;
use App\Services\ChallengeLinkService;
use App\Services\ShareCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ShareCardController extends Controller
{
    public function __construct(
        protected ShareCardService $shareCardService,
        protected ChallengeLinkService $linkService,
    ) {}

    public function show(string $token): JsonResponse
    {
        $link = $this->linkService
            ->findByPublicToken($token)
            ->loadMissing(['creatorSession.player', 'campaign', 'friendMedia']);

        return response()->json([
            'success' => true,
            'data' => $this->shareCardService->buildCard($link),
        ]);
    }

    public function image(string $token): Response
    {
        $link = $this->linkService
            ->findByPublicToken($token)
            ->loadMissing(['creatorSession.player', 'campaign', 'friendMedia']);

        $png = $this->shareCardService->renderOgImage($link);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
