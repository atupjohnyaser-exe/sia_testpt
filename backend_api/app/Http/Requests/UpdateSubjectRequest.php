<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProfileRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Profile fields only. Status, bounty, threat level and case id cannot be changed here.
 */
class UpdateSubjectRequest extends FormRequest
{
    use ProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_map(fn (array $rules) => ['sometimes', ...$rules], $this->profileRules());
    }
}
