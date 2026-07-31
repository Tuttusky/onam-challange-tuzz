<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function __construct(
        protected ChallengeService $challengeService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'player_uuid' => ['nullable', 'uuid'],
        ]);

        $player = $this->challengeService->findOrCreatePlayer([
            'uuid' => $validated['player_uuid'] ?? null,
            'name' => $validated['name'],
            'device' => $request->header('X-Device'),
            'browser' => $request->userAgent(),
            'ip' => $request->ip(),
            'country' => $request->header('X-Country'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $player->uuid,
                'name' => $player->name,
                'referral_code' => $player->referral_code,
            ],
        ], 201);
    }
}
