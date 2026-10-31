<?php

namespace App\Http\Requests;

use App\Support\Patterns;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** No minimum length on the key, so a wrong guess is always the same 401. */
    public function rules(): array
    {
        return [
            'badge_id' => ['required', 'string', 'regex:'.Patterns::BADGE],
            'access_key' => ['required', 'string', 'max:128'],
        ];
    }

    public function messages(): array
    {
        return ['badge_id.regex' => 'A badge ID is 3 to 20 letters, numbers or hyphens.'];
    }
}
