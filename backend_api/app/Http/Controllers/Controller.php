<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /** Validation for ?page and ?per_page on every list. */
    protected function pagingRules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Page size from ?per_page, kept between 1 and 100.
     */
    protected function perPage(Request $request, int $default = 20): int
    {
        return min(100, max(1, (int) $request->query('per_page', $default)));
    }
}
