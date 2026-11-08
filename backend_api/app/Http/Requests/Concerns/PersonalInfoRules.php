<?php

namespace App\Http\Requests\Concerns;

use App\Rules\PlainText;
use App\Services\PlaceService;
use App\Support\Patterns;
use App\Support\Ranks;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

/** The personal details of an officer. Shared by the application form and the Chief Admin's forms. */
trait PersonalInfoRules
{
    protected function personalRules(): array
    {
        $min = (int) config('bpd.application.min_age');
        $max = (int) config('bpd.application.max_age');

        return [
            'full_name' => ['required', 'string', 'min:5', 'max:120', 'regex:'.Patterns::NAME, 'regex:/^\S+(\s+\S+)+$/u'],
            'rank' => ['required', 'string', Rule::in(Ranks::names())],
            'date_of_birth' => [
                'required', 'date',
                'after_or_equal:'.now()->subYears($max)->toDateString(),
                'before_or_equal:'.now()->subYears($min)->toDateString(),
            ],
            // Philippine mobile number, 09 followed by nine digits. +63 and spaces are cleaned up first.
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['nullable', 'string', 'email', 'max:160'],
            'station' => ['required', 'string', 'max:100', $this->municipalityRule()],
            'address_municipality' => ['required', 'string', 'max:100', $this->municipalityRule()],
            'address_barangay' => ['nullable', 'string', 'max:100', new PlainText, $this->barangayRule('address_municipality')],
            'address_street' => ['nullable', 'string', 'max:150', new PlainText],
            'years_of_service' => ['required', 'integer', 'between:0,45', $this->serviceRule()],
        ];
    }

    protected function normalizePhone(): void
    {
        $phone = preg_replace('/[\s\-()]/', '', (string) $this->input('phone', ''));

        if (str_starts_with($phone, '+63')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '63') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 2);
        }

        $this->merge(['phone' => $phone]);
    }

    protected function municipalityRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! app(PlaceService::class)->exists((string) $value)) {
                $fail('Choose a municipality from the list.');
            }
        };
    }

    protected function barangayRule(string $municipalityField): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($municipalityField): void {
            if (! app(PlaceService::class)->barangayAllowed((string) $this->input($municipalityField), (string) $value)) {
                $fail('Choose a barangay that belongs to that municipality.');
            }
        };
    }

    /** Nobody serves for more years than their age minus 18. */
    protected function serviceRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            try {
                $age = Carbon::parse((string) $this->input('date_of_birth'))->age;
            } catch (Throwable) {
                return;
            }

            if ((int) $value > max(0, $age - 18)) {
                $fail('Years of service cannot be more than your age minus 18.');
            }
        };
    }

    public function personalMessages(): array
    {
        return [
            'full_name.regex' => 'Use your first and last name. Letters, spaces, apostrophes, periods and hyphens only.',
            'phone.regex' => 'Use a Philippine mobile number like 0917 123 4567.',
            'date_of_birth.after_or_equal' => 'The date of birth is outside the allowed age range.',
            'date_of_birth.before_or_equal' => 'You must be at least '.config('bpd.application.min_age').' years old.',
            'rank.in' => 'Choose a rank from the list.',
        ];
    }
}
