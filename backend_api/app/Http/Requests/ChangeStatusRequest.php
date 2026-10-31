<?php

namespace App\Http\Requests;

use App\Enums\SubjectStatus;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SubjectStatus::class)],
            'reason' => ['required', 'string', 'min:3', 'max:300', new PlainText],
        ];
    }
}
