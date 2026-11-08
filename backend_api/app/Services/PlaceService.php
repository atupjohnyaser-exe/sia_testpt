<?php

namespace App\Services;

use App\Models\Municipality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** Reads the real Bohol places (municipalities and barangays) that PSGC filled into the database. */
class PlaceService
{
    private const KEY = 'bpd.municipalities';

    /** @return Collection<int,Municipality> */
    public function municipalities(): Collection
    {
        return Cache::remember(self::KEY, 3600, fn () => Municipality::query()->orderBy('name')->get(['id', 'code', 'name', 'type']));
    }

    public function exists(string $municipality): bool
    {
        return $this->municipalities()->contains('name', $municipality);
    }

    /**
     * True when no barangay was given, when the barangays of that municipality have not been loaded yet,
     * or when the name is one of its real barangays.
     */
    public function barangayAllowed(string $municipality, ?string $barangay): bool
    {
        if ($barangay === null || $barangay === '') {
            return true;
        }

        $record = Municipality::query()->where('name', $municipality)->first();

        if ($record === null || ! $record->barangays()->exists()) {
            return true;
        }

        return $record->barangays()->where('name', $barangay)->exists();
    }

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
