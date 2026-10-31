<?php

namespace App\Http\Requests;

use App\Rules\PlainText;
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
            'district' => ['required', 'string', 'min:2', 'max:100', new PlainText],
            'address' => ['nullable', 'string', 'max:150', new PlainText],
            'seen_at' => ['required', 'date', 'after_or_equal:1970-01-01', 'before_or_equal:now'],
            'source' => ['nullable', Rule::in(['patrol', 'tip', 'camera', 'informant', 'other'])],
            'notes' => ['nullable', 'string', 'max:1000', new PlainText],
        ];
    }
}
