<?php

namespace App\Http\Requests;

use App\Rules\PlainText;
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
            'body' => ['required', 'string', 'min:3', 'max:5000', new PlainText],
            // An officer cannot lock a note above their own clearance.
            'access_level' => ['nullable', 'integer', 'between:1,'.(int) $this->user()->clearance_level],
        ];
    }
}
