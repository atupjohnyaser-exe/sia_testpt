<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Badge IDs and passwords. The applicant never picks either one.
 * The badge ID is random and made up (BPD-4821). Only the Chief Admin issues passwords.
 */
class CredentialService
{
    public function badgeId(): string
    {
        $prefix = (string) config('bpd.badge_prefix');

        // Four digits first. If the pool of 9000 gets crowded, move to five digits.
        foreach ([[1000, 9999], [10000, 99999]] as [$low, $high]) {
            for ($attempt = 0; $attempt < 60; $attempt++) {
                $badge = $prefix.'-'.random_int($low, $high);

                if (! User::query()->where('badge_id', $badge)->exists()) {
                    return $badge;
                }
            }
        }

        return $prefix.'-'.random_int(100000, 999999);
    }

    /** A strong random password with letters and numbers. Shown once, to the Chief Admin. */
    public function password(): string
    {
        do {
            $password = Str::password(16, symbols: false);
        } while (! $this->isStrong($password));

        return $password;
    }

    /** At least 12 characters, with letters and numbers. */
    public function isStrong(string $password): bool
    {
        return strlen($password) >= 12 && preg_match('/[A-Za-z]/', $password) === 1 && preg_match('/\d/', $password) === 1;
    }

    public function initials(string $name): string
    {
        $letters = collect(preg_split('/\s+/u', preg_replace('/[^\pL ]/u', '', $name)))
            ->filter()
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return $letters !== '' ? $letters : 'OF';
    }
}
