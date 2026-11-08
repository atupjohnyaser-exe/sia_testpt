<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Services\PlaceService;
use Illuminate\Http\JsonResponse;

/** Real Bohol places for the pickers. Public, because the access form needs them before anyone signs in. */
class PlaceController extends Controller
{
    public function municipalities(PlaceService $places): JsonResponse
    {
        return response()->json([
            'data' => $places->municipalities()->map(fn (Municipality $municipality) => [
                'id' => $municipality->id,
                'code' => $municipality->code,
                'name' => $municipality->name,
                'type' => $municipality->type,
            ])->values(),
        ]);
    }

    public function barangays(Municipality $municipality): JsonResponse
    {
        return response()->json([
            'data' => Barangay::query()
                ->where('municipality_id', $municipality->id)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }
}
