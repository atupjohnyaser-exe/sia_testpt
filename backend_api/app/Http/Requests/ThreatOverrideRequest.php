<?php

namespace App\Http\Requests;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

class ThreatOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Send level as null to remove the override. */
    public function rules(): array
    {
        return [
            'level' => ['present', 'nullable', 'integer', 'between:1,6'],
            'reason' => ['required_with:level', 'nullable', 'string', 'min:5', 'max:300', new PlainText],
        ];
    }
}
