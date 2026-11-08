<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PersonalInfoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonnelRequest extends FormRequest
{
    use PersonalInfoRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhone();
    }

    public function rules(): array
    {
        $rules = $this->personalRules();

        // The form says full_name. The account stores it as name. Address is built from its parts.
        $rules['email'] = ['nullable', 'string', 'email', 'max:160', Rule::unique('users', 'email')];

        return $rules + [
            'clearance_level' => ['required', 'integer', 'between:1,4'],
            'password' => ['nullable', 'string', 'min:12', 'max:128', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ];
    }

    public function messages(): array
    {
        return $this->personalMessages() + ['password.regex' => 'Use at least 12 characters, with letters and numbers.'];
    }
}
