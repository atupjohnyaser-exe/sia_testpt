<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssuePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['nullable', 'string', 'min:12', 'max:128', 'regex:/[A-Za-z]/', 'regex:/\d/']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least 12 characters, with letters and numbers.'];
    }
}
