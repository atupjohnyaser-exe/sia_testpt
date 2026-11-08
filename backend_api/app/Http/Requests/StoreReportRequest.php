<?php

namespace App\Http\Requests;

use App\Rules\PlainText;
use App\Services\PlaceService;
use App\Support\Patterns;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'infraction_type_id' => ['nullable', 'integer', Rule::exists('infraction_types', 'id')->where('is_active', true)],
            'occurred_at' => ['required', 'date', 'after_or_equal:1970-01-01', 'before_or_equal:now'],
            'municipality' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(PlaceService::class)->exists((string) $value)) {
                    $fail('Choose a municipality from the list.');
                }
            }],
            'barangay' => ['nullable', 'string', 'max:100', new PlainText, function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(PlaceService::class)->barangayAllowed((string) $this->input('municipality'), (string) $value)) {
                    $fail('Choose a barangay that belongs to that municipality.');
                }
            }],
            'street' => ['nullable', 'string', 'max:150', new PlainText],
            // The person may be unknown, so this is optional. An alias is fine.
            'person_name' => ['nullable', 'string', 'min:2', 'max:120', 'regex:'.Patterns::ALIAS],
            'person_description' => ['nullable', 'string', 'max:500', new PlainText],
            'narrative' => ['required', 'string', 'min:20', 'max:3000', new PlainText],
        ];
    }

    public function messages(): array
    {
        return [
            'person_name.regex' => 'Use letters, numbers, spaces, quotes, apostrophes, periods and hyphens only.',
            'narrative.min' => 'Describe what happened in at least 20 characters.',
        ];
    }
}
