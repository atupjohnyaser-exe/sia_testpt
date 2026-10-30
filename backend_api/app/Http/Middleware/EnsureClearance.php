<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClearance
{
    /**
     * Usage in routes: ->middleware('clearance:3')
     */
    public function handle(Request $request, Closure $next, string $level): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->is_active && $user->hasClearance((int) $level),
            403,
            'Your clearance level does not allow this action.'
        );

        return $next($request);
    }
}
