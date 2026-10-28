<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $badge = strtoupper(trim($request->string('badge_id')->toString()));
        $key = $request->string('access_key')->toString();

        if (config('app.demo_mode')) {
            $officer = User::firstOrCreate(['badge_id' => $badge], [
                'name' => 'Det. A. Cross', 'initials' => 'AC',
                'rank' => 'Detective', 'clearance_level' => 3, 'password' => Hash::make($key),
            ]);
        } else {
            $officer = User::where('badge_id', $badge)->first();
            if (! $officer || ! Hash::check($key, $officer->password)) {
                return response()->json(['message' => 'Badge ID or access key is incorrect.'], 422);
            }
        }

        $token = $officer->createToken('rpd-dashboard')->plainTextToken;
        AuditLog::create(['user_id' => $officer->id, 'action' => 'login', 'meta' => ['badge_id' => $badge]]);

        return response()->json(['token' => $token, 'officer' => $this->officerData($officer)]);
    }

    public function logout(Request $request)
    {
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'logout']);
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Session terminated.']);
    }

    public function me(Request $request) { return response()->json(['officer' => $this->officerData($request->user())]); }

    private function officerData(User $officer): array
    {
        return ['id' => $officer->id, 'name' => $officer->name, 'badge_id' => $officer->badge_id, 'initials' => $officer->initials, 'rank' => $officer->rank, 'clearance_level' => $officer->clearance_level];
    }
}
