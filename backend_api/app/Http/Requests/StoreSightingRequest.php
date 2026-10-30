<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSightingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'district' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:180'],
            'seen_at' => ['required', 'date', 'before_or_equal:now'],
            'source' => ['nullable', Rule::in(['patrol', 'tip', 'camera', 'informant', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
