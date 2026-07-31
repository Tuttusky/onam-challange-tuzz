<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RematchRequest extends FormRequest
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
            'type' => ['required', Rule::in(['challenge_back', 'rematch', 'new_friend'])],
            'player_uuid' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:100'],
            'friend_name' => ['nullable', 'string', 'max:100'],
            'challenge_title' => ['nullable', 'string', 'max:255'],
            'challenge_message' => ['nullable', 'string', 'max:1000'],
            'friend_media_id' => ['nullable', 'integer', 'exists:friend_media,id'],
        ];
    }
}
