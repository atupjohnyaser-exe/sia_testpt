<?php

namespace App\Policies;

use App\Enums\Clearance;
use App\Models\CaseNote;
use App\Models\User;

class CaseNotePolicy
{
    /** A note can be removed by whoever wrote it, or by a supervisor. */
    public function delete(User $user, CaseNote $note): bool
    {
        return $note->user_id === $user->id || $user->hasClearance(Clearance::Supervisor);
    }
}
