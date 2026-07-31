<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CmsPage;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaController extends Controller
{
    public function __construct(
        protected SeoService $seoService,
        protected AnalyticsService $analyticsService,
    ) {}

    public function index(Request $request): View
    {
        $seo = $this->resolveSeo($request);
        $analytics = $this->analyticsService->getTrackingScripts();

        return view('spa', compact('seo', 'analytics'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveSeo(Request $request): array
    {
        $defaults = [
            'title' => WebsiteSettingsService::getSiteName(),
            'description' => WebsiteSettingsService::getSiteTagline(),
            'theme_color' => WebsiteSettingsService::get('primary_color', '#6366f1'),
        ];

        $path = trim($request->path(), '/');

        if ($path === '') {
            $featured = Campaign::query()
                ->where('status', 'active')
                ->where('is_featured', true)
                ->with('theme')
                ->first();

            if ($featured) {
                return array_merge($defaults, $this->seoService->getCampaignMeta($featured));
            }

            $homeSeo = $this->seoService->getForPage('home');

            return array_merge($defaults, $this->seoService->toMetaTags($homeSeo));
        }

        if (str_starts_with($path, 'page/')) {
            $slug = substr($path, 5);
            $page = CmsPage::query()->where('slug', $slug)->where('is_published', true)->first();

            if ($page) {
                return array_merge($defaults, $this->seoService->getCmsPageMeta($page));
            }
        }

        if (str_starts_with($path, 'play/')) {
            $slug = substr($path, 5);
            $campaign = Campaign::query()->where('slug', $slug)->with('theme')->first();

            if ($campaign) {
                return array_merge($defaults, $this->seoService->getCampaignMeta($campaign));
            }
        }

        return $defaults;
    }
}
