<?php

namespace App\Enums;

enum Clearance: int
{
    case Clerk = 1;
    case Officer = 2;
    case Detective = 3;
    case Supervisor = 4;

    public function label(): string
    {
        return match ($this) {
            self::Clerk => 'Records Clerk',
            self::Officer => 'Officer',
            self::Detective => 'Detective',
            self::Supervisor => 'Supervisor',
        };
    }
}
