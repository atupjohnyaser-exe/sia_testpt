<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool { return (int) $this->user()->clearance_level >= 3; }
    public function rules(): array { return ['body' => ['required', 'string', 'min:3', 'max:10000']]; }
}
