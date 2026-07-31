<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PottuImage extends Model
{
    protected $fillable = [
        'campaign_id',
        'title',
        'path',
        'width',
        'height',
        'sort_order',
        'is_active',
        'is_custom',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
            'sort_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(PottuPlacement::class);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_custom', true);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->where('is_custom', false)
                ->orWhereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
    }

    public function isCustom(): bool
    {
        if ($this->is_custom) {
            return true;
        }

        return str_contains($this->path, 'pottu-custom-images');
    }

    public function isExpired(): bool
    {
        return $this->isCustom()
            && $this->expires_at !== null
            && now()->gt($this->expires_at);
    }

    public function getUrlAttribute(): string
    {
        if ($this->isExpired()) {
            return '';
        }

        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        $relativePath = $this->storageRelativePath();

        return '/storage/'.$relativePath;
    }

    public function storageRelativePath(): string
    {
        $path = $this->path;

        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return ltrim(substr($path, strlen('storage/')), '/');
        }

        return ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        $payload = [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'path' => $this->storageRelativePath(),
            'width' => $this->width,
            'height' => $this->height,
            'is_custom' => $this->isCustom(),
        ];

        if ($this->isCustom()) {
            $payload['expires_at'] = $this->expires_at?->toIso8601String();
            $payload['privacy_notice'] = 'Custom photos are stored for 7 days only and then deleted from our servers.';
        }

        return $payload;
    }
}
