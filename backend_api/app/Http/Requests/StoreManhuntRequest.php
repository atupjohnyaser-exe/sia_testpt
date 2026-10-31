<?php

namespace App\Http\Requests;

use App\Enums\ManhuntOutcome;
use App\Rules\PlainText;
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

        foreach (CostToStateCalculator::QUANTITY_LIMITS as $field => $limit) {
            $counts[$field] = ['nullable', 'integer', 'min:0', 'max:'.$limit];
        }

        return $counts + [
            'started_at' => ['required', 'date', 'after_or_equal:1970-01-01', 'before_or_equal:now'],
            // 1 second up to 48 hours.
            'duration_seconds' => ['required', 'integer', 'between:1,172800'],
            'outcome' => ['required', Rule::enum(ManhuntOutcome::class)],
            'district' => ['required', 'string', 'min:2', 'max:100', new PlainText],
            'notes' => ['nullable', 'string', 'max:2000', new PlainText],
            'infractions' => ['nullable', 'array', 'max:20'],
            'infractions.*.infraction_type_id' => ['required', 'integer', Rule::exists('infraction_types', 'id')->where('is_active', true)],
            'infractions.*.location' => ['nullable', 'string', 'max:120', new PlainText],
            'infractions.*.disposition' => ['nullable', 'string', 'max:120', new PlainText],
        ];
    }

    public function messages(): array
    {
        return [
            'duration_seconds.between' => 'A manhunt must last between 1 second and 48 hours.',
            'infractions.max' => 'Record up to 20 infractions per manhunt.',
        ];
    }
}
