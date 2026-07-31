<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CmsPageResource;
use App\Models\CmsPage;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;

class CmsController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'page' => CmsPageResource::make($page),
                'seo' => $this->seoService->getCmsPageMeta($page),
            ],
        ]);
    }
}
