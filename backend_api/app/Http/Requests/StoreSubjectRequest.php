<?php

namespace App\Http\Requests;

use App\Enums\SubjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'alias' => ['nullable', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'between:0,120'],
            // The only time status can be set directly. Later changes go through the status endpoint.
            'status' => ['nullable', Rule::enum(SubjectStatus::class)],
            'last_known_location' => ['nullable', 'string', 'max:160'],
            'height' => ['nullable', 'string', 'max:40'],
            'eye_color' => ['nullable', 'string', 'max:60'],
            'hair_color' => ['nullable', 'string', 'max:60'],
            'distinguishing_features' => ['nullable', 'string', 'max:2000'],
            // http and https only, so a javascript: link cannot be stored.
            'photo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'officer_advisory' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
