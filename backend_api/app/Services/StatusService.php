<?php

namespace App\Services;

use App\Enums\SubjectStatus;
use App\Models\StatusChange;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The only place a subject's status changes. Every change needs a reason
 * and leaves a row in status_changes.
 */
class StatusService
{
    public function change(Subject $subject, SubjectStatus $to, string $reason, ?User $by = null): StatusChange
    {
        $from = $subject->status;

        if ($from === $to) {
            throw ValidationException::withMessages([
                'status' => ['This record already has that status.'],
            ]);
        }

        $subject->forceFill(['status' => $to])->saveQuietly();

        return StatusChange::create([
            'subject_id' => $subject->id,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'changed_by' => $by?->id,
            'created_at' => now(),
        ]);
    }
}
