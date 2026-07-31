<?php

namespace App\Domains\Campaign\Handlers;

use App\Domains\Campaign\Contracts\CampaignHandlerInterface;
use App\Domains\Campaign\DTO\ScorePayload;
use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\PlayerSession;
use App\Models\PottuImage;
use App\Models\PottuPlacement;
use App\Models\PottuStyle;
use App\Services\Pottu\PottuAccuracyCalculator;
use App\Services\Pottu\PottuCustomImageService;
use App\Services\Pottu\PottuSettingsService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PottuChallengeHandler implements CampaignHandlerInterface
{
    public function __construct(
        protected PottuAccuracyCalculator $accuracyCalculator,
        protected PottuCustomImageService $customImageService,
    ) {}

    public function bootstrapPlay(Campaign $campaign, PlayerSession $session): array
    {
        $settings = PottuSettingsService::forCampaign($campaign);

        if (! ($settings['enable_game'] ?? true)) {
            throw new RuntimeException('This game is currently disabled.');
        }

        $images = PottuImage::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PottuImage $image) => $image->toPublicArray())
            ->values()
            ->all();

        $styles = PottuStyle::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PottuStyle $style) => $style->toPublicArray())
            ->values()
            ->all();

        $payload = [
            'mode' => 'pottu',
            'session_id' => $session->uuid,
            'role' => $session->role,
            'session_status' => $session->status,
            'challenge_token' => $session->challengeLink?->ensureShareToken()
                ?? $session->createdChallengeLink?->ensureShareToken(),
            'images' => $images,
            'styles' => $styles,
            'settings' => PottuSettingsService::publicForCampaign($campaign),
        ];

        if ($session->role === 'challenger') {
            $link = $session->challengeLink;
            $imageId = $link?->pottu_image_id;

            if (! $imageId && $link?->creator_session_id) {
                $creatorPlacement = PottuPlacement::query()
                    ->with('image')
                    ->where('player_session_id', $link->creator_session_id)
                    ->first();
                $imageId = $creatorPlacement?->pottu_image_id;
            } else {
                $creatorPlacement = $link?->creator_session_id
                    ? PottuPlacement::query()
                        ->with('image')
                        ->where('player_session_id', $link->creator_session_id)
                        ->first()
                    : null;
            }

            $payload['selected_image_id'] = $imageId;
            $payload['challenge'] = [
                'creator_name' => $link?->creatorSession?->player?->name,
                'friend_name' => $link?->friend_name,
                'challenge_title' => $link?->challenge_title,
                'challenge_message' => $link?->challenge_message,
            ];

            if ($creatorPlacement) {
                $payload['creator_target'] = [
                    'x' => (float) $creatorPlacement->x,
                    'y' => (float) $creatorPlacement->y,
                    'board_width' => (int) ($creatorPlacement->board_width ?: 400),
                    'board_height' => (int) ($creatorPlacement->board_height ?: 600),
                ];
                $payload['reference_size'] = [
                    'width' => (int) ($creatorPlacement->board_width ?: $creatorPlacement->image?->width ?: 400),
                    'height' => (int) ($creatorPlacement->board_height ?: $creatorPlacement->image?->height ?: 600),
                ];

                $creatorImage = $creatorPlacement->image;
                if ($creatorImage && ! $creatorImage->isExpired()) {
                    $found = false;
                    foreach ($payload['images'] as $img) {
                        if ($img['id'] === $creatorImage->id) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $payload['images'][] = $creatorImage->toPublicArray();
                    }
                }
            }
        }

        return $payload;
    }

    public function submitPlay(Campaign $campaign, PlayerSession $session, array $answers): array
    {
        if ($session->isCompleted()) {
            throw new RuntimeException('Session is already completed.');
        }

        $placement = $this->extractPlacement($answers);
        $settings = PottuSettingsService::forCampaign($campaign);
        $maxAttempts = (int) ($settings['max_attempts'] ?? 5);

        $image = PottuImage::query()
            ->where('campaign_id', $campaign->id)
            ->findOrFail($placement['image_id']);

        $this->customImageService->assertUsable($image);

        $style = null;
        if (! empty($placement['style_id'])) {
            $style = PottuStyle::query()
                ->where('campaign_id', $campaign->id)
                ->where('is_active', true)
                ->findOrFail($placement['style_id']);
        }

        $existing = PottuPlacement::query()->where('player_session_id', $session->id)->first();

        if ($existing && $existing->attempt_count >= $maxAttempts) {
            throw new RuntimeException('Maximum placement attempts reached.');
        }

        $record = DB::transaction(function () use ($session, $image, $style, $placement, $existing) {
            $attemptCount = $existing ? $existing->attempt_count + 1 : 1;

            $record = PottuPlacement::query()->updateOrCreate(
                ['player_session_id' => $session->id],
                [
                    'pottu_image_id' => $image->id,
                    'pottu_style_id' => $style?->id,
                    'x' => $this->clampCoord($placement['x']),
                    'y' => $this->clampCoord($placement['y']),
                    'size' => (int) $placement['size'],
                    'rotation' => (float) ($placement['rotation'] ?? 0),
                    'board_width' => (int) $placement['board_width'],
                    'board_height' => (int) $placement['board_height'],
                    'attempt_count' => $attemptCount,
                ]
            );

            $session->update(['status' => 'answering']);

            return $record;
        });

        return [
            'session_id' => $session->uuid,
            'saved' => true,
            'attempt_count' => $record->attempt_count,
            'max_attempts' => $maxAttempts,
        ];
    }

    public function finalizePlay(Campaign $campaign, PlayerSession $session): ScorePayload
    {
        $placement = PottuPlacement::query()
            ->where('player_session_id', $session->id)
            ->first();

        if (! $placement) {
            throw new RuntimeException('Place the pottu before confirming your challenge.');
        }

        $session->markCompleted();

        if ($session->role === 'creator') {
            $link = $session->createdChallengeLink ?? $session->challengeLink;
            if ($link) {
                $link->update(['pottu_image_id' => $placement->pottu_image_id]);

                $image = PottuImage::query()->find($placement->pottu_image_id);
                if ($image) {
                    $this->customImageService->applyChallengeExpiry($link->fresh(), $image);
                }
            }

            return new ScorePayload(
                score: 0,
                completionTimeMs: $session->fresh()->completionTimeMs(),
                accuracy: null,
                achievements: ['pottu_creator'],
                meta: [
                    'role' => 'creator',
                    'image_id' => $placement->pottu_image_id,
                ],
            );
        }

        return new ScorePayload(
            score: 0,
            completionTimeMs: $session->completionTimeMs(),
            accuracy: null,
            achievements: ['pottu_challenger'],
            meta: [
                'role' => 'challenger',
                'awaiting_compare' => true,
            ],
        );
    }

    public function comparePlay(
        Campaign $campaign,
        PlayerSession $creatorSession,
        PlayerSession $challengerSession
    ): ScorePayload {
        $settings = PottuSettingsService::forCampaign($campaign);

        $creatorPlacement = PottuPlacement::query()
            ->with('image')
            ->where('player_session_id', $creatorSession->id)
            ->firstOrFail();

        $challengerPlacement = PottuPlacement::query()
            ->where('player_session_id', $challengerSession->id)
            ->firstOrFail();

        $comparison = $this->accuracyCalculator->compare(
            $creatorPlacement,
            $challengerPlacement,
            $settings
        );

        $won = (bool) $comparison['won'];
        $winnerPlayerId = $won ? $challengerSession->player_id : $creatorSession->player_id;

        return new ScorePayload(
            score: (float) $comparison['points'],
            completionTimeMs: $challengerSession->completionTimeMs(),
            accuracy: (float) $comparison['accuracy_percent'],
            achievements: $won ? ['pottu_win', 'band:'.($comparison['label'] ?? 'miss')] : ['pottu_miss'],
            meta: [
                'mode' => 'pottu',
                'won' => $won,
                'pixel_distance' => $comparison['pixel_distance'],
                'band' => $comparison['band'],
                'stars' => $comparison['stars'],
                'label' => $comparison['label'],
                'accuracy_percent' => $comparison['accuracy_percent'],
                'winner_player_id' => $winnerPlayerId,
                'can_create_next_challenge' => $won,
                'creator_position' => $comparison['creator_position'],
                'friend_position' => $comparison['friend_position'],
                'reference_size' => $comparison['reference_size'],
                'details' => [
                    'pixel_distance' => $comparison['pixel_distance'],
                    'band' => $comparison['band'],
                    'creator_position' => $comparison['creator_position'],
                    'friend_position' => $comparison['friend_position'],
                    'reference_size' => $comparison['reference_size'],
                    'image_url' => $creatorPlacement->image?->url,
                ],
                'match_count' => $won ? 1 : 0,
                'total_questions' => 1,
                'match_percent' => $won ? (float) $comparison['accuracy_percent'] : 0,
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    protected function extractPlacement(array $answers): array
    {
        $placement = $answers['placement'] ?? ($answers[0] ?? null);

        if (! is_array($placement)) {
            throw new InvalidArgumentException('Pottu placement payload is required.');
        }

        $required = ['image_id', 'x', 'y', 'size', 'board_width', 'board_height'];
        foreach ($required as $field) {
            if (! isset($placement[$field])) {
                throw new InvalidArgumentException("Missing placement field: {$field}");
            }
        }

        return $placement;
    }

    protected function clampCoord(float $value): float
    {
        return max(0, min(1, round($value, 6)));
    }
}
