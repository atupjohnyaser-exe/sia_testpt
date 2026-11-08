<?php

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Enums\Clearance;
use App\Services\AuditLogger;
use App\Support\Denied;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClearance
{
    public function __construct(private AuditLogger $audit)
    {
    }

    /**
     * Usage in routes: ->middleware('clearance:3')
     * A refused request is written to the audit log and answered with the levels involved,
     * so the app can say exactly what is missing.
     */
    public function handle(Request $request, Closure $next, string $level): Response
    {
        $user = $request->user();
        $required = Clearance::tryFrom((int) $level) ?? Clearance::Chief;

        if ($user === null || ! $user->is_active) {
            return Denied::response(null, $required, 'Your account is not active.');
        }

        if (! $user->hasClearance($required)) {
            $this->audit->record(AuditAction::AccessDenied, null, [
                'method' => $request->method(),
                'path' => $request->path(),
                'required' => $required->value,
                'level' => $user->clearance_level,
            ]);

            return Denied::response($user, $required);
        }

        return $next($request);
    }
}
