<?php

namespace App\Http\Requests;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAccessRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Level is needed for every rank except a Detective, whose level is set automatically. */
    public function rules(): array
    {
        return [
            'level' => ['nullable', 'integer', 'between:1,4'],
            // Leave empty and the app makes a strong random password.
            'password' => ['nullable', 'string', 'min:12', 'max:128', 'regex:/[A-Za-z]/', 'regex:/\d/'],
            'note' => ['nullable', 'string', 'max:300', new PlainText],
        ];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least 12 characters, with letters and numbers.'];
    }
}
