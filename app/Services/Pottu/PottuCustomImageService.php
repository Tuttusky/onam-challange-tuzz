<?php

namespace App\Services\Pottu;

use App\Models\ChallengeLink;
use App\Models\PottuImage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PottuCustomImageService
{
    public const DEFAULT_RETENTION_DAYS = 7;

    public static function retentionDays(): int
    {
        $days = (int) (PottuSettingsService::globalSettings()['custom_image_retention_days'] ?? self::DEFAULT_RETENTION_DAYS);

        return max(1, $days);
    }

    public static function challengeValidDays(): int
    {
        $days = (int) (PottuSettingsService::globalSettings()['custom_challenge_valid_days'] ?? self::DEFAULT_RETENTION_DAYS);

        return max(1, $days);
    }

    public static function expiresAt(?CarbonInterface $from = null): CarbonInterface
    {
        return ($from ?? now())->copy()->addDays(static::retentionDays());
    }

    public function purgeExpired(): int
    {
        $images = PottuImage::query()
            ->where('is_custom', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $purged = 0;

        foreach ($images as $image) {
            $this->deleteImageFile($image);

            ChallengeLink::query()
                ->where('pottu_image_id', $image->id)
                ->update([
                    'is_active' => false,
                    'pottu_image_id' => null,
                ]);

            $image->delete();
            $purged++;
        }

        if ($purged > 0) {
            Log::info('Purged expired pottu custom images.', ['count' => $purged]);
        }

        return $purged;
    }

    public function applyChallengeExpiry(ChallengeLink $link, PottuImage $image): void
    {
        if (! $image->isCustom()) {
            return;
        }

        $imageExpiry = $image->expires_at ?? static::expiresAt();
        $challengeExpiry = now()->addDays(static::challengeValidDays());
        $targetExpiry = $imageExpiry->lt($challengeExpiry) ? $imageExpiry : $challengeExpiry;

        if ($link->expires_at === null || $link->expires_at->gt($targetExpiry)) {
            $link->update(['expires_at' => $targetExpiry]);
        }
    }

    public function assertUsable(PottuImage $image): void
    {
        if ($image->isCustom() && $image->isExpired()) {
            throw new \RuntimeException('This custom photo has expired. Please upload a new image.');
        }
    }

    protected function deleteImageFile(PottuImage $image): void
    {
        $path = $image->storageRelativePath();

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
