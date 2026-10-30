<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3', 'max:10000'],
            // An officer cannot lock a note above their own clearance.
            'access_level' => ['nullable', 'integer', 'between:1,'.(int) $this->user()->clearance_level],
        ];
    }
}
