<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PottuStyle extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'path',
        'type',
        'default_size',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://') || str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return Storage::disk('public')->url($this->path);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'type' => $this->type,
            'default_size' => $this->default_size,
        ];
    }
}
