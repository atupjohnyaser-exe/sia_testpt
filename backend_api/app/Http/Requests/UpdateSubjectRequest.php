<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool { return (int) $this->user()->clearance_level >= 3; }
    public function rules(): array
    {
        $subject = $this->route('subject');
        return [
            'case_id' => ['sometimes', 'required', 'string', 'max:32', Rule::unique('subjects', 'case_id')->ignore($subject)],
            'name' => ['sometimes', 'required', 'string', 'max:160'], 'alias' => ['nullable', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'between:0,120'],
            'status' => ['sometimes', Rule::in(['WANTED', 'IN_CUSTODY', 'PAROLED', 'CLEARED'])],
            'classification' => ['sometimes', Rule::in(['Priority Alpha', 'Priority Beta', 'Under review'])],
            'last_known_location' => ['nullable', 'string', 'max:160'], 'height' => ['nullable', 'string', 'max:40'],
            'eye_color' => ['nullable', 'string', 'max:60'], 'hair_color' => ['nullable', 'string', 'max:60'],
            'distinguishing_features' => ['nullable', 'string', 'max:3000'], 'photo_url' => ['nullable', 'url', 'max:2048'],
            'risk_score' => ['sometimes', 'integer', 'between:0,100'], 'risk_override' => ['sometimes', 'boolean'],
            'officer_advisory' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
