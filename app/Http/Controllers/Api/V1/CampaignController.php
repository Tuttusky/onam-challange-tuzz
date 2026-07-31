<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $campaigns = Campaign::query()
            ->with('theme')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Campaign $campaign) => $campaign->isActive())
            ->values();

        return response()->json([
            'success' => true,
            'data' => CampaignResource::collection($campaigns),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $campaign = Campaign::query()
            ->with('theme')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => CampaignResource::make($campaign),
        ]);
    }
}
