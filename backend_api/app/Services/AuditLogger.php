<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Subject;
use App\Models\User;

class AuditLogger
{
    public function record(AuditAction $action, ?int $subjectId = null, array $meta = [], ?int $userId = null): ?AuditLog
    {
        $userId ??= auth()->id();

        // Seeding and other console jobs have no officer, so they stay out of the audit log.
        if ($userId === null && app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        return AuditLog::create([
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'action' => $action,
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Opening a record is logged once per officer per window, so refreshes
     * and arrow key browsing do not flood the log.
     */
    public function recordView(Subject $subject, User $user): void
    {
        $alreadyLogged = AuditLog::query()
            ->where('user_id', $user->id)
            ->where('subject_id', $subject->id)
            ->where('action', AuditAction::Viewed->value)
            ->where('created_at', '>=', now()->subMinutes((int) config('rockport.view_log_minutes')))
            ->exists();

        if (! $alreadyLogged) {
            $this->record(AuditAction::Viewed, $subject->id, [], $user->id);
        }
    }
}
