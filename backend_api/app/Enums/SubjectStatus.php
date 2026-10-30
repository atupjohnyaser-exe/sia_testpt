<?php

namespace App\Enums;

enum SubjectStatus: string
{
    case Wanted = 'WANTED';
    case InCustody = 'IN_CUSTODY';
    case Paroled = 'PAROLED';
    case Cleared = 'CLEARED';

    public function label(): string
    {
        return match ($this) {
            self::Wanted => 'Wanted',
            self::InCustody => 'In custody',
            self::Paroled => 'Paroled',
            self::Cleared => 'Cleared',
        };
    }
}
