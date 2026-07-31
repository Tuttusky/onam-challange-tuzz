<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VisitEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'max:100'],
            'campaign_slug' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'player_uuid' => ['nullable', 'uuid'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
