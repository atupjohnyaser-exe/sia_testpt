<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Profile fields only. Status, bounty, threat level and case id cannot be changed here.
 */
class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'alias' => ['sometimes', 'nullable', 'string', 'max:120'],
            'age' => ['sometimes', 'nullable', 'integer', 'between:0,120'],
            'last_known_location' => ['sometimes', 'nullable', 'string', 'max:160'],
            'height' => ['sometimes', 'nullable', 'string', 'max:40'],
            'eye_color' => ['sometimes', 'nullable', 'string', 'max:60'],
            'hair_color' => ['sometimes', 'nullable', 'string', 'max:60'],
            'distinguishing_features' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'photo_url' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'officer_advisory' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
