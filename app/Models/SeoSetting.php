<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoSetting extends Model
{
    protected $fillable = [
        'seoable_type',
        'seoable_id',
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
    ];

    protected function casts(): array
    {
        return [
            'schema_markup' => 'array',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
