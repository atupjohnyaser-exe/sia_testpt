<?php

namespace App\Services;

use App\Models\Subject;

class RiskScoreService
{
    private const WEIGHTS = [
        'violent-crime' => 30, 'robbery' => 28, 'possession-of-weapons' => 25, 'burglary' => 20,
        'drugs' => 18, 'criminal-damage-arson' => 16, 'vehicle-crime' => 14,
        'theft-from-the-person' => 12, 'other-theft' => 10, 'shoplifting' => 8,
        'public-order' => 8, 'bicycle-theft' => 6, 'anti-social-behaviour' => 4, 'other-crime' => 8,
    ];

    public function weight(string $category): int
    {
        $category = strtolower($category);
        return self::WEIGHTS[$category] ?? (str_contains($category, 'violent') ? 30 : 8);
    }

    public function severity(string $category): string
    {
        $weight = $this->weight($category);
        return $weight >= 20 ? 'HIGH' : ($weight >= 10 ? 'MEDIUM' : 'LOW');
    }

    public function classification(int $score): string
    {
        return $score >= 80 ? 'Priority Alpha' : ($score >= 55 ? 'Priority Beta' : 'Under review');
    }

    public function recalculate(Subject $subject): Subject
    {
        $score = $subject->risk_override
            ? (int) $subject->risk_score
            : min(99, 15 + $subject->offenses()->get()->sum(fn ($offense) => $this->weight($offense->category)));
        $effects = $subject->offenses()->pluck('status_effect')->all();
        $statusOrder = ['WANTED' => 4, 'IN_CUSTODY' => 3, 'PAROLED' => 2, 'CLEARED' => 1];
        $status = collect($effects)->sortByDesc(fn ($effect) => $statusOrder[$effect] ?? 0)->first() ?? 'WANTED';

        $subject->forceFill([
            'risk_score' => $score,
            'classification' => $subject->risk_override ? $subject->classification : $this->classification($score),
            'status' => $status,
        ])->save();

        return $subject->refresh();
    }
}
