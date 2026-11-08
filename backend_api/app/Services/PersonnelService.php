<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\Clearance;
use App\Models\User;
use App\Support\Ranks;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/** The Chief Admin's tools for the people who have access. Every rule that limits them lives here. */
class PersonnelService
{
    private const PERSONAL = ['name', 'rank', 'clearance_level', 'email', 'phone', 'date_of_birth', 'station', 'address', 'years_of_service'];

    public function __construct(
        private CredentialService $credentials,
        private AuditLogger $audit,
    ) {
    }

    /** @return array{user: User, password: string} */
    public function create(array $data, User $chief): array
    {
        $this->guardLevel($data['rank'], (int) $data['clearance_level']);

        $password = $this->chosenPassword($data['password'] ?? null);

        $user = User::create(Arr::only($data, self::PERSONAL) + [
            'badge_id' => $this->credentials->badgeId(),
            'initials' => $this->credentials->initials($data['name']),
            'is_active' => true,
            'password' => $password,
        ]);

        $user->forceFill([
            'approved_by' => $chief->id,
            'approved_at' => now(),
            'password_issued_by' => $chief->id,
            'password_issued_at' => now(),
        ])->save();

        $this->audit->record(AuditAction::PersonnelCreated, null, ['badge_id' => $user->badge_id, 'level' => $user->clearance_level]);

        return ['user' => $user, 'password' => $password];
    }

    public function update(User $user, array $data, User $chief): User
    {
        $this->guardEditable($user);

        $this->guardLevel($data['rank'] ?? $user->rank, (int) ($data['clearance_level'] ?? $user->clearance_level));

        $user->fill(Arr::only($data, self::PERSONAL));

        if ($user->isDirty('name')) {
            $user->initials = $this->credentials->initials($user->name);
        }

        $changed = array_keys($user->getDirty());
        $user->save();

        $this->audit->record(AuditAction::PersonnelUpdated, null, ['badge_id' => $user->badge_id, 'changed' => $changed]);

        return $user;
    }

    /** Issues a new password and signs the person out everywhere. Returns the password, shown once. */
    public function issuePassword(User $user, ?string $password, User $chief): string
    {
        $this->guardEditable($user);

        $plain = $this->chosenPassword($password);

        $user->forceFill([
            'password' => $plain,
            'password_issued_by' => $chief->id,
            'password_issued_at' => now(),
        ])->save();

        $user->tokens()->delete();

        $this->audit->record(AuditAction::PasswordIssued, null, ['badge_id' => $user->badge_id]);

        return $plain;
    }

    public function setActive(User $user, bool $active, User $chief): User
    {
        $this->guardEditable($user);

        if (! $active && $user->is($chief)) {
            throw ValidationException::withMessages(['user' => ['You cannot deactivate your own account.']]);
        }

        $user->forceFill(['is_active' => $active])->save();

        if (! $active) {
            $user->tokens()->delete();
        }

        $this->audit->record($active ? AuditAction::PersonnelActivated : AuditAction::PersonnelDeactivated, null, ['badge_id' => $user->badge_id]);

        return $user;
    }

    /** Chief Admin accounts are never edited through the app. Use the command line. */
    private function guardEditable(User $user): void
    {
        if ($user->hasClearance(Clearance::Chief)) {
            throw ValidationException::withMessages(['user' => ['Chief Admin accounts can only be managed from the command line.']]);
        }
    }

    /** The level must fit the rank, and the app never hands out level 5. */
    private function guardLevel(string $rank, int $level): void
    {
        $cap = min(Ranks::cap($rank), 4);

        if ($cap === 0) {
            throw ValidationException::withMessages(['rank' => ['That rank cannot be given access through the app.']]);
        }

        if ($level < 1 || $level > $cap) {
            throw ValidationException::withMessages(['clearance_level' => ["{$rank} can hold level {$cap} at most."]]);
        }
    }

    private function chosenPassword(?string $password): string
    {
        if ($password === null || $password === '') {
            return $this->credentials->password();
        }

        if (! $this->credentials->isStrong($password)) {
            throw ValidationException::withMessages(['password' => ['Use at least 12 characters, with letters and numbers.']]);
        }

        return $password;
    }
}
