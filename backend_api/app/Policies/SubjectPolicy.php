<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function view(User $user, Subject $subject): bool { return (int) $user->clearance_level >= 3; }
    public function update(User $user, Subject $subject): bool { return (int) $user->clearance_level >= 3; }
    public function delete(User $user, Subject $subject): bool { return (int) $user->clearance_level >= 3; }
}
