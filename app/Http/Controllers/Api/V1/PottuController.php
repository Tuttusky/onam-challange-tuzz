<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\PottuImage;
use App\Models\PottuStyle;
use App\Services\Pottu\PottuImageUploadService;
use App\Services\Pottu\PottuSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PottuController extends Controller
{
    public function __construct(
        protected PottuImageUploadService $imageUploadService,
    ) {}

    public function uploadImage(Request $request, string $slug): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('slug', $slug)
            ->where('type', Campaign::TYPE_POTTU)
            ->firstOrFail();

        if (! $campaign->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is not active.',
            ], 422);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:10240'],
        ]);

        try {
            $image = $this->imageUploadService->storeCustomImage($campaign, $request->file('image'));
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $image->toPublicArray(),
        ], 201);
    }

    public function config(string $slug): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('slug', $slug)
            ->where('type', Campaign::TYPE_POTTU)
            ->firstOrFail();

        if (! $campaign->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is not active.',
            ], 422);
        }

        $images = PottuImage::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PottuImage $image) => $image->toPublicArray())
            ->values();

        $styles = PottuStyle::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PottuStyle $style) => $style->toPublicArray())
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'campaign' => [
                    'slug' => $campaign->slug,
                    'name' => $campaign->name,
                    'type' => $campaign->type,
                ],
                'images' => $images,
                'styles' => $styles,
                'settings' => PottuSettingsService::publicForCampaign($campaign),
            ],
        ]);
    }
}
