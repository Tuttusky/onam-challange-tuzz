<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'category_id',
        'type',
        'title',
        'description',
        'image',
        'icon',
        'difficulty',
        'points',
        'sort_order',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'points' => 'integer',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }
}
