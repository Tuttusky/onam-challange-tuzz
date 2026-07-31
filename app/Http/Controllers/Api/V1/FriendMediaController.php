<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFriendMediaRequest;
use App\Models\FriendMedia;
use App\Services\ChallengeService;
use App\Services\FriendMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FriendMediaController extends Controller
{
    public function __construct(
        protected FriendMediaService $mediaService,
        protected ChallengeService $challengeService,
    ) {}

    public function store(StoreFriendMediaRequest $request): JsonResponse
    {
        try {
            $playerId = null;

            if ($request->filled('player_uuid')) {
                $player = $this->challengeService->findOrCreatePlayer([
                    'uuid' => $request->validated('player_uuid'),
                    'name' => $request->validated('name') ?? 'Player',
                ]);
                $playerId = $player->id;
            }

            $media = match ($request->validated('media_type')) {
                'upload' => $this->mediaService->storeUpload($request->file('photo'), $playerId),
                'avatar' => $this->mediaService->storeAvatar((int) $request->validated('avatar_id'), $playerId),
                'initial' => $this->mediaService->storeInitial($request->validated('initial'), $playerId),
                default => throw new InvalidArgumentException('Invalid media type.'),
            };
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mediaService->toPublicArray($media) + ['id' => $media->id],
        ], 201);
    }

    public function avatars(): JsonResponse
    {
        $avatars = \App\Models\FriendAvatar::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($avatar) => [
                'id' => $avatar->id,
                'name' => $avatar->name,
                'slug' => $avatar->slug,
                'url' => asset($avatar->path),
            ]);

        return response()->json([
            'success' => true,
            'data' => $avatars,
        ]);
    }

    public function show(string $token): StreamedResponse|JsonResponse
    {
        $media = FriendMedia::query()
            ->where('public_token', $token)
            ->where('type', 'upload')
            ->firstOrFail();

        if ($media->isExpired() || ! $media->storage_path) {
            return response()->json(['success' => false, 'message' => 'Media expired.'], 404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        if (! $disk->exists($media->storage_path)) {
            return response()->json(['success' => false, 'message' => 'Media not found.'], 404);
        }

        return response()->stream(function () use ($disk, $media) {
            echo $disk->get($media->storage_path);
        }, 200, [
            'Content-Type' => $media->mime ?? 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
