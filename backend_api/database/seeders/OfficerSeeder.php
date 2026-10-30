<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OfficerSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('rockport.seed_password') ?: (app()->isProduction() ? null : 'demo1234');

        if ($password === null) {
            $this->command?->warn('SEED_OFFICER_PASSWORD is not set, so no officer accounts were created. Use php artisan rpd:create-officer.');

            return;
        }

        $officers = [
            ['RP-1001', 'Supt. R. Hale', 'RH', 'Superintendent', 4],
            ['RP-1047', 'Det. A. Cross', 'AC', 'Detective', 3],
            ['RP-1102', 'Ofc. T. Reyes', 'TR', 'Officer', 2],
            ['RP-1200', 'Clerk M. Ono', 'MO', 'Records Clerk', 1],
        ];

        foreach ($officers as [$badge, $name, $initials, $rank, $level]) {
            User::updateOrCreate(
                ['badge_id' => $badge],
                ['name' => $name, 'initials' => $initials, 'rank' => $rank, 'clearance_level' => $level, 'is_active' => true, 'password' => $password]
            );
        }

        if (! config('rockport.seed_password')) {
            $this->command?->info('Demo officers RP-1001, RP-1047, RP-1102 and RP-1200 created. Password: demo1234 (local only).');
        }
    }
}
