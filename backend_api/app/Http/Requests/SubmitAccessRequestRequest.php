<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PersonalInfoRules;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAccessRequestRequest extends FormRequest
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
        return $this->personalRules() + [
            'justification' => ['required', 'string', 'min:10', 'max:500', new PlainText],
            'declaration' => ['accepted'],
            // A real person never sees this field. Bots fill it in.
            'website' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return $this->personalMessages() + [
            'declaration.accepted' => 'Confirm that the information is true.',
        ];
    }
}
