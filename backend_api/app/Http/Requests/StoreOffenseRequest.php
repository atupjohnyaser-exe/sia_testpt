<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOffenseRequest extends FormRequest
{
    public function authorize(): bool { return (int) $this->user()->clearance_level >= 3; }
    public function rules(): array
    {
        return ['occurred_on' => ['required', 'date'], 'category' => ['required', 'string', 'max:120'], 'location' => ['required', 'string', 'max:180'], 'disposition' => ['nullable', 'string', 'max:180']];
    }
}
