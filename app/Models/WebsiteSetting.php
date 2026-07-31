<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("website_setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("website_setting.{$key}");
        Cache::forget('website_settings.all');

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return static::query()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }
}
