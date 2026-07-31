<?php

namespace App\Services;

use App\Models\AnalyticsSetting;
use App\Models\VisitEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return AnalyticsSetting::get($key, $default);
    }

    public function setSetting(string $key, mixed $value): AnalyticsSetting
    {
        return AnalyticsSetting::set($key, $value);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function trackEvent(string $eventType, array $data = []): VisitEvent
    {
        return VisitEvent::query()->create([
            'campaign_id' => $data['campaign_id'] ?? null,
            'player_id' => $data['player_id'] ?? null,
            'event_type' => $eventType,
            'source' => $data['source'] ?? null,
            'device' => $data['device'] ?? request()->header('X-Device'),
            'browser' => $data['browser'] ?? request()->userAgent(),
            'country' => $data['country'] ?? null,
            'ip' => $data['ip'] ?? request()->ip(),
            'meta' => $data['meta'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCampaignSummary(?int $campaignId = null, int $days = 30): array
    {
        $since = now()->subDays($days);

        $query = VisitEvent::query()->where('created_at', '>=', $since);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $events = $query->get();

        return [
            'period_days' => $days,
            'total_events' => $events->count(),
            'unique_players' => $events->pluck('player_id')->filter()->unique()->count(),
            'by_event_type' => $events->groupBy('event_type')->map->count()->sortDesc()->all(),
            'by_source' => $events->groupBy('source')->map->count()->sortDesc()->all(),
            'by_country' => $events->groupBy('country')->map->count()->sortDesc()->take(10)->all(),
            'by_device' => $events->groupBy('device')->map->count()->sortDesc()->all(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function getDailyTrend(?int $campaignId = null, int $days = 14): Collection
    {
        $since = now()->subDays($days)->startOfDay();

        $query = VisitEvent::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $since)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        return $query->get();
    }

    public function isTrackingEnabled(): bool
    {
        return filter_var($this->getSetting('tracking_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->getSetting('google_analytics_id');
    }

    public function getFacebookPixelId(): ?string
    {
        return $this->getSetting('facebook_pixel_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrackingScripts(): array
    {
        if (! $this->isTrackingEnabled()) {
            return [];
        }

        return array_filter([
            'google_analytics_id' => $this->getGoogleAnalyticsId(),
            'facebook_pixel_id' => $this->getFacebookPixelId(),
            'custom_head_scripts' => $this->getSetting('custom_head_scripts'),
            'custom_body_scripts' => $this->getSetting('custom_body_scripts'),
        ]);
    }
}
