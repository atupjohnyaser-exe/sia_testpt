<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PersonalInfoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonnelRequest extends FormRequest
{
    use PersonalInfoRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->normalizePhone();
        }
    }

    public function rules(): array
    {
        $rules = array_map(fn (array $rule) => ['sometimes', ...$rule], $this->personalRules());
        $rules['email'] = ['sometimes', 'nullable', 'string', 'email', 'max:160', Rule::unique('users', 'email')->ignore($this->route('user'))];

        return $rules + ['clearance_level' => ['sometimes', 'integer', 'between:1,4']];
    }

    public function messages(): array
    {
        return $this->personalMessages();
    }
}
