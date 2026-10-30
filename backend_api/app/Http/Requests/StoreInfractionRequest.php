<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInfractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'infraction_type_id' => ['required', 'integer', Rule::exists('infraction_types', 'id')->where('is_active', true)],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'location' => ['required', 'string', 'max:180'],
            'disposition' => ['nullable', 'string', 'max:180'],
        ];
    }
}
