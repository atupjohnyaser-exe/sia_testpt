<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSightingRequest;
use App\Http\Resources\SightingResource;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class SightingController extends Controller
{
    public function store(StoreSightingRequest $request, Subject $subject): JsonResponse
    {
        $sighting = $subject->sightings()->create($request->validated() + [
            'reported_by' => $request->user()->id,
        ]);

        // The most recent sighting becomes the last known location.
        if ($subject->sightings()->latest('seen_at')->first()?->is($sighting)) {
            $subject->forceFill(['last_known_location' => $sighting->district])->saveQuietly();
        }

        return (new SightingResource($sighting))->response()->setStatusCode(201);
    }
}
