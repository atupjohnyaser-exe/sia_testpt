<?php

namespace App\Enums;

enum Clearance: int
{
    case Patrol = 1;
    case Senior = 2;
    case Detective = 3;
    case Supervisor = 4;
    case Chief = 5;

    public function label(): string
    {
        return match ($this) {
            self::Patrol => 'Patrol Officer',
            self::Senior => 'Senior Officer',
            self::Detective => 'Detective',
            self::Supervisor => 'Supervisor',
            self::Chief => 'Chief Admin',
        };
    }
}
