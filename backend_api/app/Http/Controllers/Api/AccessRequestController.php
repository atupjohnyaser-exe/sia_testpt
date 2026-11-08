<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Enums\Clearance;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAccessRequestRequest;
use App\Models\AccessRequest;
use App\Services\AccessRequestService;
use App\Services\AuditLogger;
use App\Support\Ranks;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/** The public side of asking for access. No login needed, and nothing here can log anyone in. */
class AccessRequestController extends Controller
{
    /** Ranks and the level each one gets, for the form. */
    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'ranks' => array_map(fn (array $rank) => [
                'name' => $rank['name'],
                'level' => $rank['level'],
                'label' => Clearance::from($rank['level'])->label(),
                'auto' => $rank['auto'],
            ], Ranks::applicable()),
            'min_age' => config('bpd.application.min_age'),
            'max_age' => config('bpd.application.max_age'),
        ]]);
    }

    public function store(SubmitAccessRequestRequest $request, AccessRequestService $service, AuditLogger $audit): JsonResponse
    {
        // A bot filled the hidden field. Answer like it worked and save nothing.
        if ($request->filled('website')) {
            return response()->json(['data' => ['reference' => 'ACC-'.strtoupper(Str::random(10)), 'status' => AccessRequest::PENDING]], 201);
        }

        $record = $service->submit($request->validated(), $request->ip());

        $audit->record(AuditAction::AccessRequested, null, [
            'reference' => $record->reference,
            'rank' => $record->rank,
            'level' => $record->requested_level,
        ]);

        return response()->json(['data' => [
            'reference' => $record->reference,
            'status' => $record->status,
            'rank' => $record->rank,
            'requested_level' => $record->requested_level,
            'requested_label' => Clearance::from($record->requested_level)->label(),
            'auto_level' => $record->auto_level,
        ]], 201);
    }

    /** Status only. No personal details leave this endpoint. */
    public function show(string $reference): JsonResponse
    {
        $record = AccessRequest::query()->with('user:id,badge_id')->where('reference', $reference)->firstOrFail();

        return response()->json(['data' => [
            'reference' => $record->reference,
            'status' => $record->status,
            'rank' => $record->rank,
            'requested_level' => $record->requested_level,
            'submitted_at' => $record->created_at,
            'reviewed_at' => $record->reviewed_at,
            'approved_level' => $record->approved_level,
            // The badge ID is not secret. The password is handed over in person by the Chief Admin.
            'badge_id' => $record->status === AccessRequest::APPROVED ? $record->user?->badge_id : null,
            'note' => $record->status === AccessRequest::REJECTED ? $record->review_note : null,
        ]]);
    }
}
