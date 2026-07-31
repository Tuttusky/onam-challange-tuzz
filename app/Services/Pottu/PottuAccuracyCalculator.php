<?php

namespace App\Services\Pottu;

use App\Models\PottuImage;
use App\Models\PottuPlacement;

class PottuAccuracyCalculator
{
    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function compare(PottuPlacement $creator, PottuPlacement $challenger, array $settings): array
    {
        $image = $creator->image ?? PottuImage::query()->find($creator->pottu_image_id);
        $refWidth = (int) ($image?->width ?: $creator->board_width ?: 400);
        $refHeight = (int) ($image?->height ?: $creator->board_height ?: 600);

        $pixelDistance = $this->pixelDistance(
            (float) $creator->x,
            (float) $creator->y,
            (float) $challenger->x,
            (float) $challenger->y,
            $refWidth,
            $refHeight
        );

        $band = $this->resolveBand($pixelDistance, $settings);
        $failThreshold = (int) ($settings['fail_threshold_px'] ?? 30);
        $won = $pixelDistance <= $failThreshold;
        $maxDistance = max($refWidth, $refHeight);
        $accuracy = $maxDistance > 0
            ? max(0, min(100, round((1 - ($pixelDistance / $maxDistance)) * 100, 2)))
            : 0;

        if (! $won) {
            $band = [
                'min' => $failThreshold + 1,
                'max' => null,
                'stars' => 0,
                'label' => 'Miss',
                'points' => 0,
            ];
        }

        return [
            'pixel_distance' => round($pixelDistance, 2),
            'accuracy' => $won ? (float) ($band['points'] ?? 0) : 0.0,
            'accuracy_percent' => $accuracy,
            'band' => $band,
            'won' => $won,
            'points' => $won ? (int) ($band['points'] ?? 0) : 0,
            'stars' => (int) ($band['stars'] ?? 0),
            'label' => (string) ($band['label'] ?? 'Miss'),
            'creator_position' => $creator->toResultArray(),
            'friend_position' => $challenger->toResultArray(),
            'reference_size' => [
                'width' => $refWidth,
                'height' => $refHeight,
            ],
        ];
    }

    public function pixelDistance(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        int $refWidth,
        int $refHeight
    ): float {
        $dx = ($x1 - $x2) * $refWidth;
        $dy = ($y1 - $y2) * $refHeight;

        return sqrt(($dx * $dx) + ($dy * $dy));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    protected function resolveBand(float $pixelDistance, array $settings): array
    {
        $bands = $settings['tolerance_bands'] ?? PottuSettingsService::defaults()['tolerance_bands'];
        $distance = (int) round($pixelDistance);

        foreach ($bands as $band) {
            $min = (int) ($band['min'] ?? 0);
            $max = isset($band['max']) ? (int) $band['max'] : null;

            if ($distance >= $min && ($max === null || $distance <= $max)) {
                return $band;
            }
        }

        return [
            'min' => 31,
            'max' => null,
            'stars' => 0,
            'label' => 'Miss',
            'points' => 0,
        ];
    }
}
