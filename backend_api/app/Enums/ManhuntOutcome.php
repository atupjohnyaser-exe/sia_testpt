<?php

namespace App\Enums;

enum ManhuntOutcome: string
{
    case Evaded = 'EVADED';
    case Apprehended = 'APPREHENDED';

    public function label(): string
    {
        return match ($this) {
            self::Evaded => 'Evaded',
            self::Apprehended => 'Apprehended',
        };
    }
}
