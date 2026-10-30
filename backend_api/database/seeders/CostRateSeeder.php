<?php

namespace Database\Seeders;

use App\Models\CostRate;
use Illuminate\Database\Seeder;

class CostRateSeeder extends Seeder
{
    public function run(): void
    {
        // The order here is the order shown on the cost to state screen.
        $rates = [
            ['officers_involved', 'Officers Deployed', 250],
            ['officers_injured', 'Officers Injured', 12_000],
            ['units_stood_down', 'Units Stood Down', 5_000],
            ['checkpoints', 'Checkpoints Deployed', 500],
            ['k9_units', 'K-9 Units Deployed', 1_200],
            ['helicopters', 'Helicopters Deployed', 2_000],
            ['property_damage_incidents', 'Property Damage Incidents', 2_500],
            ['insurance_claims', 'Insurance Claims', 500],
        ];

        foreach ($rates as [$code, $label, $cost]) {
            CostRate::updateOrCreate(['code' => $code], ['label' => $label, 'unit_cost' => $cost]);
        }
    }
}
