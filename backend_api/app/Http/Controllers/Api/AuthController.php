<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\OfficerResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * A well formed bcrypt hash that matches nothing. Checking against it when the badge
     * does not exist keeps the response time the same as for a wrong password.
     */
    private const DUMMY_HASH = '$2y$12$abcdefghijklmnopqrstuuK7sOHLHf9Zp5WqZ1uC3XGdIJvB0Z5s6';

    public function login(LoginRequest $request, AuditLogger $audit): JsonResponse
    {
        $badge = strtoupper(trim($request->string('badge_id')->toString()));
        $key = $request->string('access_key')->toString();

        $officer = User::where('badge_id', $badge)->first();

        // Demo mode only creates accounts for badges that do not exist yet.
        // An existing officer always needs the right key.
        if ($officer === null && config('rockport.demo_mode')) {
            $officer = $this->createDemoOfficer($badge, $key);
        }

        if ($officer === null) {
            Hash::check($key, self::DUMMY_HASH);
        }

        $valid = $officer !== null && $officer->is_active && Hash::check($key, $officer->password);

        if (! $valid) {
            $audit->record(AuditAction::LoginFailed, null, ['badge_id' => $badge], $officer?->id);

            return response()->json(['message' => 'Badge ID or access key is incorrect.'], 401);
        }

        $officer->tokens()->where('expires_at', '<', now())->delete();

        $expiresAt = now()->addMinutes((int) config('rockport.token_ttl_minutes'));
        $token = $officer->createToken('rpd-dashboard', ['*'], $expiresAt)->plainTextToken;

        $officer->forceFill(['last_login_at' => now()])->saveQuietly();
        $audit->record(AuditAction::Login, null, [], $officer->id);

        return response()->json([
            'token' => $token,
            'expires_at' => $expiresAt,
            'officer' => new OfficerResource($officer),
        ]);
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        $audit->record(AuditAction::Logout);

        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Signed out.']);
    }

    public function me(Request $request): OfficerResource
    {
        return new OfficerResource($request->user());
    }

    private function createDemoOfficer(string $badge, string $key): User
    {
        $letters = preg_replace('/[^A-Z0-9]/', '', $badge);

        return User::create([
            'badge_id' => $badge,
            'name' => 'Officer '.$badge,
            'initials' => substr($letters, 0, 2) ?: 'OF',
            'rank' => 'Officer',
            'clearance_level' => (int) config('rockport.demo_clearance'),
            'password' => $key,
        ]);
    }
}
