<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CmsPage;
use App\Models\SeoSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SeoService
{
    public function getForPage(string $pageKey): ?SeoSetting
    {
        return SeoSetting::query()
            ->where('page_key', $pageKey)
            ->whereNull('seoable_type')
            ->first();
    }

    public function getForModel(Model $model): ?SeoSetting
    {
        return SeoSetting::query()
            ->where('seoable_type', $model->getMorphClass())
            ->where('seoable_id', $model->getKey())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertForPage(string $pageKey, array $data): SeoSetting
    {
        return SeoSetting::query()->updateOrCreate(
            ['page_key' => $pageKey, 'seoable_type' => null, 'seoable_id' => null],
            $this->filterSeoData($data)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertForModel(Model $model, array $data): SeoSetting
    {
        return SeoSetting::query()->updateOrCreate(
            [
                'seoable_type' => $model->getMorphClass(),
                'seoable_id' => $model->getKey(),
            ],
            $this->filterSeoData($data)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toMetaTags(?SeoSetting $seo): array
    {
        if (! $seo) {
            return [];
        }

        return array_filter([
            'title' => $seo->meta_title,
            'description' => $seo->meta_description,
            'keywords' => $seo->meta_keywords,
            'og_title' => $seo->og_title ?? $seo->meta_title,
            'og_description' => $seo->og_description ?? $seo->meta_description,
            'og_image' => $seo->og_image,
            'twitter_card' => $seo->twitter_card ?? 'summary_large_image',
            'canonical_url' => $seo->canonical_url,
            'robots' => $seo->robots,
            'schema_markup' => $seo->schema_markup,
            'google_verification' => $seo->google_verification,
            'bing_verification' => $seo->bing_verification,
            'facebook_verification' => $seo->facebook_verification,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function getCampaignMeta(Campaign $campaign): array
    {
        $seo = $this->getForModel($campaign) ?? $this->getForPage('campaign.'.$campaign->slug);

        $defaults = [
            'title' => $campaign->name,
            'description' => $campaign->description,
            'og_title' => $campaign->name,
            'og_description' => $campaign->description,
            'og_image' => $campaign->theme?->logo,
        ];

        return array_merge($defaults, $this->toMetaTags($seo));
    }

    public function getCmsPageMeta(CmsPage $page): array
    {
        $seo = $this->getForModel($page) ?? $this->getForPage('cms.'.$page->slug);

        $defaults = [
            'title' => $page->title,
            'description' => data_get($page->meta, 'description'),
        ];

        return array_merge($defaults, $this->toMetaTags($seo));
    }

    /**
     * @return Collection<int, SeoSetting>
     */
    public function listPageSettings(): Collection
    {
        return SeoSetting::query()
            ->whereNotNull('page_key')
            ->orderBy('page_key')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterSeoData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'page_key',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'og_title',
            'og_description',
            'og_image',
            'twitter_card',
            'canonical_url',
            'schema_markup',
            'robots',
            'google_verification',
            'bing_verification',
            'facebook_verification',
        ]));
    }

    public function generateSitemap(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $urls = collect([
            ['loc' => $baseUrl.'/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => $baseUrl.'/leaderboard', 'changefreq' => 'daily', 'priority' => '0.8'],
        ]);

        Campaign::query()
            ->where('status', 'active')
            ->get(['slug', 'updated_at'])
            ->each(function (Campaign $campaign) use ($urls, $baseUrl) {
                $urls->push([
                    'loc' => $baseUrl.'/play/'.$campaign->slug,
                    'lastmod' => $campaign->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                ]);
            });

        CmsPage::query()
            ->where('is_published', true)
            ->get(['slug', 'updated_at'])
            ->each(function (CmsPage $page) use ($urls, $baseUrl) {
                $urls->push([
                    'loc' => $baseUrl.'/page/'.$page->slug,
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ]);
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($entry['loc'], ENT_XML1)."</loc>\n";
            if (! empty($entry['lastmod'])) {
                $xml .= '    <lastmod>'.$entry['lastmod']."</lastmod>\n";
            }
            $xml .= '    <changefreq>'.($entry['changefreq'] ?? 'weekly')."</changefreq>\n";
            $xml .= '    <priority>'.($entry['priority'] ?? '0.5')."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    public function generateRobots(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api',
            '',
            'Sitemap: '.$baseUrl.'/sitemap.xml',
        ]);
    }
}
