<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\QuestionOption */
class QuestionOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'value' => $this->value,
            'icon' => $this->icon,
            'image' => $this->image,
            'points' => $this->points,
            'sort_order' => $this->sort_order,
        ];
    }
}
