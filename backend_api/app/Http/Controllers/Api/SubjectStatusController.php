<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Enums\Clearance;
use App\Enums\SubjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\ThreatOverrideRequest;
use App\Http\Resources\StatusChangeResource;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\AuditLogger;
use App\Services\StatusService;
use App\Services\SubjectStatsService;
use App\Support\Denied;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectStatusController extends Controller
{
    public function index(Request $request, Subject $subject)
    {
        $request->validate($this->pagingRules());

        return StatusChangeResource::collection(
            $subject->statusChanges()->with('changer:id,name,badge_id')->latest('id')->paginate($this->perPage($request))
        );
    }

    /**
     * Detectives change status. Clearing a record, or re-opening a cleared one, rewrites the
     * history of a person, so it needs the Chief Admin.
     */
    public function store(ChangeStatusRequest $request, Subject $subject, StatusService $status): SubjectResource|JsonResponse
    {
        $to = SubjectStatus::from($request->validated('status'));
        $user = $request->user();

        if (($to === SubjectStatus::Cleared || $subject->status === SubjectStatus::Cleared) && ! $user->hasClearance(Clearance::Chief)) {
            return Denied::response($user, Clearance::Chief, 'No clearance. Clearing a record, or re-opening a cleared one, needs the Chief Admin (level 5).');
        }

        $status->change($subject, $to, $request->validated('reason'), $user);

        return new SubjectResource($subject->refresh());
    }

    /** Supervisors can pin the threat level above or below the calculated one. */
    public function threat(ThreatOverrideRequest $request, Subject $subject, SubjectStatsService $stats, AuditLogger $audit): SubjectResource
    {
        $level = $request->validated('level');

        $stats->setOverride($subject, $level === null ? null : (int) $level, $request->validated('reason'), $request->user());

        $audit->record(AuditAction::ThreatOverridden, $subject->id, [
            'level' => $level,
            'reason' => $request->validated('reason'),
        ]);

        return new SubjectResource($subject->refresh());
    }
}
