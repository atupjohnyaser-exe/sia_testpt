<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\InfractionResource;
use App\Http\Resources\ManhuntResource;
use App\Http\Resources\StatusChangeResource;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\AuditLogger;
use App\Services\CostToStateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Everything the printable case file needs, in one response.
 * It is a POST, not a GET, because every case file generated is written to the audit log.
 */
class CaseFileController extends Controller
{
    public function show(Request $request, Subject $subject, AuditLogger $audit, CostToStateCalculator $costs): JsonResponse
    {
        $audit->record(AuditAction::CaseFileGenerated, $subject->id, ['format' => 'print']);

        $subject->load('advisoryReviewer');

        $manhunts = $subject->manhunts()->withCount('infractions')->orderByDesc('bounty_value')->get();

        $locations = DB::table('sightings')
            ->where('subject_id', $subject->id)
            ->groupBy('district')
            ->selectRaw('district, COUNT(*) AS sightings, MAX(seen_at) AS last_seen')
            ->orderByDesc('last_seen')
            ->get()
            ->map(fn ($row) => [
                'district' => $row->district,
                'sightings' => (int) $row->sightings,
                'last_seen' => $row->last_seen,
            ]);

        return response()->json(['data' => [
            'generated_at' => now(),
            'generated_by' => $request->user()->only(['name', 'badge_id', 'rank']),
            'subject' => (new SubjectResource($subject))->resolve($request),
            'infractions' => InfractionResource::collection(
                $subject->infractions()->with('type')->orderByDesc('occurred_on')->orderByDesc('id')->get()
            )->resolve($request),
            'manhunts' => ManhuntResource::collection($manhunts)->resolve($request),
            'cost_to_state' => $costs->aggregate($manhunts),
            'locations' => $locations,
            'status_history' => StatusChangeResource::collection(
                $subject->statusChanges()->with('changer:id,name,badge_id')->orderByDesc('id')->get()
            )->resolve($request),
        ]]);
    }
}
