<?php

namespace App\Services;

class OffenseClassifier
{
    public function statusEffect(?string $outcome): string
    {
        $value = strtolower($outcome ?? '');
        if (str_contains($value, 'prison') || str_contains($value, 'custody')) return 'IN_CUSTODY';
        if (str_contains($value, 'community') || str_contains($value, 'suspended') || str_contains($value, 'probation')) return 'PAROLED';
        if (str_contains($value, 'unable to prosecute') || str_contains($value, 'no suspect') || str_contains($value, 'no further action')) return 'CLEARED';
        return 'WANTED';
    }

    public function charge(string $category): string
    {
        return str($category)->replace('-', ' ')->title()->toString();
    }
}
