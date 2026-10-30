<?php

namespace Database\Seeders;

use App\Models\InfractionType;
use Illuminate\Database\Seeder;

class InfractionTypeSeeder extends Seeder
{
    public function run(): void
    {
        // code, name, severity, fine, bounty
        $types = [
            ['trespassing', 'Trespassing', 'LOW', 250, 5_000],
            ['property-damage', 'Damage to Property', 'MEDIUM', 1_500, 25_000],
            ['resisting-arrest', 'Resisting Arrest', 'MEDIUM', 2_500, 40_000],
            ['evading-officers', 'Evading Officers', 'MEDIUM', 3_000, 60_000],
            ['fraud', 'Fraud', 'MEDIUM', 5_000, 75_000],
            ['weapons-possession', 'Weapons Possession', 'HIGH', 7_500, 100_000],
            ['burglary', 'Burglary', 'HIGH', 7_500, 120_000],
            ['assault', 'Assault', 'HIGH', 8_000, 140_000],
            ['drug-trafficking', 'Drug Trafficking', 'HIGH', 10_000, 150_000],
            ['arson', 'Arson', 'HIGH', 12_000, 180_000],
            ['assaulting-officer', 'Assaulting an Officer', 'HIGH', 10_000, 200_000],
            ['armed-robbery', 'Armed Robbery', 'HIGH', 15_000, 300_000],
        ];

        foreach ($types as [$code, $name, $severity, $fine, $bounty]) {
            InfractionType::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'severity' => $severity, 'fine_amount' => $fine, 'bounty_value' => $bounty, 'is_active' => true]
            );
        }
    }
}
