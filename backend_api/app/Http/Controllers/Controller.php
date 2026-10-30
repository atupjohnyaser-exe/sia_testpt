<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Page size from ?per_page, kept between 1 and 100.
     */
    protected function perPage(Request $request, int $default = 20): int
    {
        return min(100, max(1, (int) $request->query('per_page', $default)));
    }
}
