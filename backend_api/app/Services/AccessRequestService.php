<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AccessRequest;
use App\Models\User;
use App\Support\Ranks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The life of an application for access:
 *   submitted by the officer  ->  reviewed by the Chief Admin  ->  approved (account created) or rejected.
 * The level comes from the rank and is capped by it. The badge ID is random. The password is issued by the Chief Admin.
 */
class AccessRequestService
{
    public function __construct(
        private CredentialService $credentials,
        private AuditLogger $audit,
    ) {
    }

    public function submit(array $data, ?string $ip): AccessRequest
    {
        $this->guardDuplicate($data);

        $rank = Ranks::find($data['rank']);

        $request = AccessRequest::create([
            'reference' => $this->newReference(),
            'full_name' => $data['full_name'],
            'rank' => $rank['name'],
            // A Detective's level is set on the application. Every other rank starts at what its rank allows.
            'requested_level' => $rank['level'],
            'auto_level' => $rank['auto'],
            'date_of_birth' => $data['date_of_birth'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'station' => $data['station'],
            'address_municipality' => $data['address_municipality'],
            'address_barangay' => $data['address_barangay'] ?? null,
            'address_street' => $data['address_street'] ?? null,
            'years_of_service' => $data['years_of_service'],
            'justification' => $data['justification'],
            'ip_address' => $ip,
        ]);

        return $request->refresh();
    }

    /**
     * @return array{request: AccessRequest, user: User, password: string}
     */
    public function approve(AccessRequest $accessRequest, User $chief, ?int $level, ?string $password, ?string $note): array
    {
        return DB::transaction(function () use ($accessRequest, $chief, $level, $password, $note) {
            // Lock the row so two clicks cannot create two accounts.
            $request = AccessRequest::query()->whereKey($accessRequest->id)->lockForUpdate()->firstOrFail();

            if (! $request->isPending()) {
                throw ValidationException::withMessages(['request' => ['This application was already reviewed.']]);
            }

            $cap = min(Ranks::cap($request->rank), 4);
            $level ??= $request->auto_level ? $request->requested_level : null;

            if ($level === null) {
                throw ValidationException::withMessages(['level' => ['Choose a clearance level for this officer.']]);
            }

            if ($level < 1 || $level > $cap) {
                throw ValidationException::withMessages(['level' => ["{$request->rank} can hold level {$cap} at most."]]);
            }

            if ($request->email && User::query()->where('email', $request->email)->exists()) {
                throw ValidationException::withMessages(['request' => ['Another account already uses this email address.']]);
            }

            if ($password !== null && ! $this->credentials->isStrong($password)) {
                throw ValidationException::withMessages(['password' => ['Use at least 12 characters, with letters and numbers.']]);
            }

            $plain = $password ?: $this->credentials->password();

            $user = User::create([
                'badge_id' => $this->credentials->badgeId(),
                'name' => $request->full_name,
                'initials' => $this->credentials->initials($request->full_name),
                'rank' => $request->rank,
                'clearance_level' => $level,
                'is_active' => true,
                'password' => $plain,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'station' => $request->station,
                'address' => $this->address($request->address_street, $request->address_barangay, $request->address_municipality),
                'years_of_service' => $request->years_of_service,
            ]);

            $user->forceFill([
                'access_request_id' => $request->id,
                'approved_by' => $chief->id,
                'approved_at' => now(),
                'password_issued_by' => $chief->id,
                'password_issued_at' => now(),
            ])->save();

            $request->forceFill([
                'status' => AccessRequest::APPROVED,
                'reviewed_by' => $chief->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'approved_level' => $level,
                'user_id' => $user->id,
            ])->save();

            $this->audit->record(AuditAction::AccessApproved, null, [
                'reference' => $request->reference,
                'badge_id' => $user->badge_id,
                'level' => $level,
            ]);

            return ['request' => $request->refresh(), 'user' => $user, 'password' => $plain];
        });
    }

    public function reject(AccessRequest $accessRequest, User $chief, string $note): AccessRequest
    {
        return DB::transaction(function () use ($accessRequest, $chief, $note) {
            $request = AccessRequest::query()->whereKey($accessRequest->id)->lockForUpdate()->firstOrFail();

            if (! $request->isPending()) {
                throw ValidationException::withMessages(['request' => ['This application was already reviewed.']]);
            }

            $request->forceFill([
                'status' => AccessRequest::REJECTED,
                'reviewed_by' => $chief->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $this->audit->record(AuditAction::AccessRejected, null, ['reference' => $request->reference]);

            return $request->refresh();
        });
    }

    public function address(?string $street, ?string $barangay, string $municipality): string
    {
        return implode(', ', array_filter([$street, $barangay ? 'Brgy. '.$barangay : null, $municipality]));
    }

    /** One waiting application per phone number, and per name and birth date. */
    private function guardDuplicate(array $data): void
    {
        $waiting = AccessRequest::query()->where('status', AccessRequest::PENDING);

        $same = (clone $waiting)->where('phone', $data['phone'])->exists()
            || (clone $waiting)->where('full_name', $data['full_name'])->whereDate('date_of_birth', $data['date_of_birth'])->exists();

        if ($same) {
            throw ValidationException::withMessages([
                'full_name' => ['An application for this officer is already waiting for review.'],
            ]);
        }
    }

    private function newReference(): string
    {
        do {
            $reference = 'ACC-'.strtoupper(Str::random(10));
        } while (AccessRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
