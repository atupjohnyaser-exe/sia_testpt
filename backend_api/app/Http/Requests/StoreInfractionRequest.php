<?php

namespace App\Http\Requests;

use App\Rules\PlainText;
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
            // Up to tomorrow, so officers in time zones ahead of the server can still enter today's date.
            'occurred_on' => ['required', 'date', 'after_or_equal:1970-01-01', 'before_or_equal:tomorrow'],
            'location' => ['required', 'string', 'min:2', 'max:120', new PlainText],
            'disposition' => ['nullable', 'string', 'max:120', new PlainText],
        ];
    }
}
