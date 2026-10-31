<?php

namespace App\Http\Requests;

use App\Enums\SubjectStatus;
use App\Http\Requests\Concerns\ProfileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    use ProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->profileRules() + [
            // The only time status can be set directly. Later changes go through the status endpoint.
            'status' => ['nullable', Rule::enum(SubjectStatus::class)],
        ];
    }
}
