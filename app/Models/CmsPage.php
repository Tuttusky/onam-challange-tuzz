<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CmsPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function seoSettings(): MorphMany
    {
        return $this->morphMany(SeoSetting::class, 'seoable');
    }
}
