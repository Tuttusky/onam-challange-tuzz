<?php

namespace App\Http\Middleware;

use App\Models\PlayerSession;
use App\Services\ChallengeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePlayerSession
{
    public function __construct(
        protected ChallengeService $challengeService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $sessionUuid = $request->route('uuid');

        if (! $sessionUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Session identifier is required.',
            ], 400);
        }

        $token = $request->header('X-Player-Session');

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Player session token is required.',
            ], 401);
        }

        $session = PlayerSession::query()
            ->with(['campaign', 'player', 'challengeLink'])
            ->where('uuid', $sessionUuid)
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Player session not found.',
            ], 404);
        }

        if (! $this->challengeService->validateSessionToken($session, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid player session token.',
            ], 403);
        }

        $request->attributes->set('player_session', $session);

        return $next($request);
    }
}
