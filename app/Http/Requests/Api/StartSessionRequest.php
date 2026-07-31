<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
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
            'campaign_slug' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'player_uuid' => ['nullable', 'uuid'],
            'referral_code' => ['nullable', 'string', 'max:16'],
            'friend_name' => ['nullable', 'string', 'max:100'],
            'challenge_title' => ['nullable', 'string', 'max:255'],
            'challenge_message' => ['nullable', 'string', 'max:1000'],
            'friend_media_id' => ['nullable', 'integer', 'exists:friend_media,id'],
            'parent_link_id' => ['nullable', 'integer', 'exists:challenge_links,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'campaign_slug.required' => 'A campaign slug is required to start a session.',
            'name.required' => 'Please provide your name to start the challenge.',
        ];
    }
}
