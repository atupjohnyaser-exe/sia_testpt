<?php

namespace App\Enums;

enum ThreatLevel: int
{
    case Routine = 1;
    case Elevated = 2;
    case High = 3;
    case Severe = 4;
    case Critical = 5;
    case Maximum = 6;

    /**
     * The highest level whose minimum bounty the subject reaches.
     * Thresholds live in config/rockport.php and must be in ascending order.
     */
    public static function fromBounty(int $bounty): self
    {
        $level = 1;

        foreach (config('rockport.threat_thresholds') as $candidate => $minimum) {
            if ($bounty >= $minimum) {
                $level = $candidate;
            }
        }

        return self::from($level);
    }

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Elevated => 'Elevated',
            self::High => 'High',
            self::Severe => 'Severe',
            self::Critical => 'Critical',
            self::Maximum => 'Maximum',
        };
    }

    public function response(): string
    {
        return match ($this) {
            self::Routine => 'Standard monitoring.',
            self::Elevated => 'Notify patrol units in the district.',
            self::High => 'Approach with caution. Two officers minimum.',
            self::Severe => 'Request backup before contact.',
            self::Critical => 'Request heavy backup. Air support on standby.',
            self::Maximum => 'Command approval needed before contact. Heavy backup and air support.',
        };
    }
}
