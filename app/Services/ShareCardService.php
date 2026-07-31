<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeLink;

class ShareCardService
{
    public function __construct(
        protected FriendMediaService $mediaService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildCard(ChallengeLink $link): array
    {
        $link->loadMissing(['creatorSession.player', 'campaign', 'friendMedia']);

        $creatorName = $link->creatorSession?->player?->name ?? 'Someone';
        $gameName = $link->campaign?->name ?? 'Challenge';
        $score = $link->creator_score !== null ? (float) $link->creator_score : null;

        if ($link->campaign?->type === Campaign::TYPE_POTTU) {
            $score = null;
        }

        return [
            'headline' => "🔥 {$creatorName} challenged you!",
            'game_name' => $gameName,
            'creator_name' => $creatorName,
            'friend_name' => $link->friend_name,
            'challenge_title' => $link->challenge_title,
            'challenge_message' => $link->challenge_message,
            'creator_score' => $score,
            'creator_score_label' => $this->formatScoreLabel($link, $score),
            'cta' => 'Play Now',
            'challenge_url' => url($link->sharePath()),
            'friend_media' => $this->mediaService->toPublicArray($link->friendMedia),
            'og_image_url' => route('api.share-cards.image', ['token' => $link->ensureShareToken()]),
        ];
    }

    public function renderOgImage(ChallengeLink $link): string
    {
        $card = $this->buildCard($link);

        $width = 1200;
        $height = 630;
        $image = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($image, 27, 67, 50);
        $accent = imagecolorallocate($image, 255, 107, 53);
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 200, 220, 210);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $title = $card['headline'];
        $game = 'Game: '.$card['game_name'];
        $scoreLine = $card['creator_score_label'];
        $cta = $card['cta'];

        imagestring($image, 5, 60, 80, $this->truncate($title, 45), $white);
        imagestring($image, 4, 60, 180, $this->truncate($game, 50), $muted);
        imagestring($image, 4, 60, 240, $this->truncate($scoreLine, 50), $accent);
        imagestring($image, 5, 60, 360, $cta, $white);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png ?: '';
    }

    protected function formatScoreLabel(ChallengeLink $link, ?float $score): string
    {
        if ($link->campaign?->type === Campaign::TYPE_POTTU) {
            return 'Find the exact pottu position — Think you can beat it?';
        }

        if ($score === null) {
            return 'Think you can beat it?';
        }

        if ($link->campaign?->type === 'dare_challenge') {
            return "Completed {$score} questions — Think you can beat it?";
        }

        return sprintf("%s's Score: %s — Think you can beat it?", $link->creatorSession?->player?->name ?? 'Creator', $this->formatNumber($score));
    }

    protected function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    protected function truncate(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, $max - 3).'...';
    }
}
