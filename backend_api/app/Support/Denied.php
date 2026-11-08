<?php

namespace App\Support;

use App\Enums\Clearance;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/** The 403 response used everywhere a clearance level is too low. The app shows it as a warning. */
final class Denied
{
    public static function response(?User $user, Clearance $required, ?string $message = null): JsonResponse
    {
        $yours = $user?->clearance();

        $message ??= $user === null
            ? 'You are not signed in.'
            : "No clearance. This needs {$required->label()} (level {$required->value}) or higher. You are {$yours->label()} (level {$yours->value}).";

        return response()->json([
            'message' => $message,
            'code' => 'clearance_required',
            'required_level' => $required->value,
            'required_label' => $required->label(),
            'your_level' => $yours?->value,
            'your_label' => $yours?->label(),
        ], 403);
    }
}
