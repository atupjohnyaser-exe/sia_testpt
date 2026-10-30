<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInfractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'served' => ['required_without:fine_paid', 'boolean'],
            'fine_paid' => ['required_without:served', 'boolean'],
        ];
    }
}
