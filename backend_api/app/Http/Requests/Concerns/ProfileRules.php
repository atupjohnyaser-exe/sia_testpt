<?php

namespace App\Http\Requests\Concerns;

use App\Rules\PlainText;
use App\Support\Patterns;

/** The profile fields of a subject. Shared by the create and update requests. */
trait ProfileRules
{
    protected function profileRules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120', 'regex:'.Patterns::NAME],
            'alias' => ['nullable', 'string', 'min:2', 'max:80', 'regex:'.Patterns::ALIAS],
            'age' => ['nullable', 'integer', 'between:1,120'],
            'last_known_location' => ['nullable', 'string', 'max:120', new PlainText],
            'height' => ['nullable', 'string', 'max:20', 'regex:'.Patterns::HEIGHT],
            'eye_color' => ['nullable', 'string', 'max:30', 'regex:'.Patterns::COLOR],
            'hair_color' => ['nullable', 'string', 'max:30', 'regex:'.Patterns::COLOR],
            'distinguishing_features' => ['nullable', 'string', 'max:500', new PlainText],
            // http and https only, so a javascript: link cannot be stored. Uploads go through the photo endpoint.
            'photo_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'officer_advisory' => ['nullable', 'string', 'max:2000', new PlainText],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Use letters, spaces, apostrophes, periods and hyphens only.',
            'alias.regex' => 'Use letters, numbers, spaces, quotes, apostrophes, periods and hyphens only.',
            'height.regex' => 'Use a format like 5\'8" or 173 cm.',
            'eye_color.regex' => 'Use letters only.',
            'hair_color.regex' => 'Use letters only.',
            'photo_url.url' => 'Use a full link that starts with http:// or https://.',
        ];
    }
}
