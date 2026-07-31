<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CmsPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $outputPath = 'public/sitemap.xml'
    ) {}

    public function handle(): void
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $urls = collect([
            [
                'loc' => $baseUrl.'/',
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
        ]);

        Campaign::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->each(function (Campaign $campaign) use ($urls, $baseUrl): void {
                $urls->push([
                    'loc' => "{$baseUrl}/campaigns/{$campaign->slug}",
                    'lastmod' => $campaign->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ]);
            });

        CmsPage::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get(['slug', 'updated_at'])
            ->each(function (CmsPage $page) use ($urls, $baseUrl): void {
                $urls->push([
                    'loc' => "{$baseUrl}/pages/{$page->slug}",
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ]);
            });

        $xml = $this->buildXml($urls->all());
        $absolutePath = base_path($this->outputPath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $xml);

        Log::info('Sitemap generated.', [
            'path' => $this->outputPath,
            'url_count' => $urls->count(),
        ]);
    }

    /**
     * @param  array<int, array<string, string|null>>  $urls
     */
    protected function buildXml(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars((string) $url['loc'], ENT_XML1).'</loc>';

            if (! empty($url['lastmod'])) {
                $lines[] = '    <lastmod>'.htmlspecialchars((string) $url['lastmod'], ENT_XML1).'</lastmod>';
            }

            if (! empty($url['changefreq'])) {
                $lines[] = '    <changefreq>'.htmlspecialchars((string) $url['changefreq'], ENT_XML1).'</changefreq>';
            }

            if (! empty($url['priority'])) {
                $lines[] = '    <priority>'.htmlspecialchars((string) $url['priority'], ENT_XML1).'</priority>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Sitemap generation job failed.', [
            'output_path' => $this->outputPath,
            'error' => $exception?->getMessage(),
        ]);
    }
}
