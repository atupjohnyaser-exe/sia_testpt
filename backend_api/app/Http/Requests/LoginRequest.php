<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['badge_id' => ['required', 'string', 'max:40'], 'access_key' => ['required', 'string', 'max:255']]; }
}
