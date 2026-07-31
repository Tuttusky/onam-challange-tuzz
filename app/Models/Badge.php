<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'slug',
        'image',
        'min_match_percent',
        'max_match_percent',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_match_percent' => 'integer',
            'max_match_percent' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function challengeResults(): HasMany
    {
        return $this->hasMany(ChallengeResult::class);
    }

    public function matchesPercent(float $percent): bool
    {
        return $percent >= $this->min_match_percent && $percent <= $this->max_match_percent;
    }
}
