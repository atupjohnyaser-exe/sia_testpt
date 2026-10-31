<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * For free text fields (notes, advisories, locations, reasons).
 * Rejects anything that is not plain readable text:
 *   - invalid UTF-8 and control characters (except new lines and tabs)
 *   - invisible and direction-changing characters that can make text look like something it is not
 *   - HTML tags
 * The React app escapes everything it shows, so this is a second layer, not the only one.
 */
class PlainText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $fail('The :attribute contains characters that are not valid text.');

            return;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            $fail('The :attribute contains control characters that are not allowed.');

            return;
        }

        // Zero width characters, left to right and right to left marks and overrides, byte order mark.
        if (preg_match('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', $value)) {
            $fail('The :attribute contains hidden characters that are not allowed.');

            return;
        }

        if (preg_match('/<\s*\/?\s*[a-z!?]/i', $value)) {
            $fail('The :attribute may not contain HTML.');
        }
    }
}
