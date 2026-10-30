<?php

namespace App\Http\Requests;

use App\Enums\ManhuntOutcome;
use App\Services\CostToStateCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManhuntRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $counts = [];

        foreach (CostToStateCalculator::QUANTITY_FIELDS as $field) {
            $counts[$field] = ['nullable', 'integer', 'min:0', 'max:100000'];
        }

        return $counts + [
            'started_at' => ['required', 'date', 'before_or_equal:now'],
            'duration_seconds' => ['required', 'integer', 'between:1,172800'],
            'outcome' => ['required', Rule::enum(ManhuntOutcome::class)],
            'district' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'infractions' => ['nullable', 'array', 'max:50'],
            'infractions.*.infraction_type_id' => ['required', 'integer', Rule::exists('infraction_types', 'id')->where('is_active', true)],
            'infractions.*.location' => ['nullable', 'string', 'max:180'],
            'infractions.*.disposition' => ['nullable', 'string', 'max:180'],
        ];
    }
}
