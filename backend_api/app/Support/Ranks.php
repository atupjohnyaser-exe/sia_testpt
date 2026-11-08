<?php

namespace App\Support;

/** The rank ladder from config/bpd.php. A rank decides the highest clearance a person may hold. */
final class Ranks
{
    /** @return array<int,array{name:string,level:int,auto:bool,apply:bool}> */
    public static function all(): array
    {
        return array_map(fn (array $rank) => $rank + ['auto' => false, 'apply' => true], config('bpd.ranks'));
    }

    /** Ranks a person can pick on the application form. */
    public static function applicable(): array
    {
        return array_values(array_filter(self::all(), fn (array $rank) => $rank['apply']));
    }

    public static function names(): array
    {
        return array_column(self::applicable(), 'name');
    }

    public static function find(string $name): ?array
    {
        foreach (self::all() as $rank) {
            if ($rank['name'] === $name) {
                return $rank;
            }
        }

        return null;
    }

    /** The highest clearance this rank may hold. 0 for an unknown rank. */
    public static function cap(string $name): int
    {
        return self::find($name)['level'] ?? 0;
    }

    public static function isAuto(string $name): bool
    {
        return self::find($name)['auto'] ?? false;
    }
}
