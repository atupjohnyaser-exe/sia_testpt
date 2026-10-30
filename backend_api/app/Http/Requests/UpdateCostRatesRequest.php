<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCostRatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.code' => ['required', 'string', 'exists:cost_rates,code'],
            'rates.*.unit_cost' => ['required', 'integer', 'between:0,10000000'],
        ];
    }
}
