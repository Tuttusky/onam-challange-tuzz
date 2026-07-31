<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CmsPage */
class CmsPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'meta' => $this->meta,
            'sort_order' => $this->sort_order,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
