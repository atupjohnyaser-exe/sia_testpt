<?php

namespace App\Services;

use App\Enums\ManhuntOutcome;
use App\Enums\ThreatLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the numbers stored on a subject from its infractions and manhunts.
 *
 *   bounty = bounty of unserved infractions + bounty of all manhunts
 *   threat = supervisor override if there is one, otherwise from the bounty
 *
 * Status is not touched here. Only StatusService changes status.
 */
class SubjectStatsService
{
    public function refresh(Subject $subject): Subject
    {
        $infractions = DB::table('infractions')
            ->where('subject_id', $subject->id)
            ->selectRaw(
                'COUNT(*) AS total,
                 COALESCE(SUM(CASE WHEN served_at IS NULL THEN 1 ELSE 0 END), 0) AS unserved,
                 COALESCE(SUM(CASE WHEN served_at IS NULL THEN bounty_value ELSE 0 END), 0) AS bounty,
                 COALESCE(SUM(CASE WHEN fine_paid_at IS NULL THEN fine_amount ELSE 0 END), 0) AS fines_due,
                 COALESCE(SUM(CASE WHEN fine_paid_at IS NOT NULL THEN fine_amount ELSE 0 END), 0) AS fines_paid'
            )
            ->first();

        $manhunts = DB::table('manhunts')
            ->where('subject_id', $subject->id)
            ->selectRaw(
                'COALESCE(SUM(bounty_value), 0) AS bounty,
                 COALESCE(SUM(cost_to_state), 0) AS cost,
                 COALESCE(SUM(CASE WHEN outcome = ? THEN 1 ELSE 0 END), 0) AS evaded,
                 COALESCE(SUM(CASE WHEN outcome = ? THEN 1 ELSE 0 END), 0) AS apprehended',
                [ManhuntOutcome::Evaded->value, ManhuntOutcome::Apprehended->value]
            )
            ->first();

        $bounty = (int) $infractions->bounty + (int) $manhunts->bounty;

        $subject->forceFill([
            'bounty' => $bounty,
            'cost_to_state' => (int) $manhunts->cost,
            'fines_due' => (int) $infractions->fines_due,
            'fines_paid' => (int) $infractions->fines_paid,
            'total_infractions' => (int) $infractions->total,
            'unserved_infractions' => (int) $infractions->unserved,
            'times_evaded' => (int) $manhunts->evaded,
            'times_apprehended' => (int) $manhunts->apprehended,
            'threat_level' => $subject->threat_override ?? ThreatLevel::fromBounty($bounty)->value,
        ])->saveQuietly();

        return $subject;
    }

    /** Pass null as the level to remove an override. */
    public function setOverride(Subject $subject, ?int $level, ?string $reason, User $by): Subject
    {
        $subject->forceFill([
            'threat_override' => $level,
            'threat_override_reason' => $level === null ? null : $reason,
            'threat_override_by' => $level === null ? null : $by->id,
        ])->saveQuietly();

        return $this->refresh($subject);
    }
}
