<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OfficerSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('bpd.seed_password') ?: (app()->isProduction() ? null : 'demo1234');

        if ($password === null) {
            $this->command?->warn('SEED_OFFICER_PASSWORD is not set, so no officer accounts were created. Use php artisan bpd:create-officer.');

            return;
        }

        // badge, name, initials, rank, level, station, phone, born, years of service
        $officers = [
            ['BPD-1000', 'Col. R. Villanueva', 'RV', 'Provincial Director', 5, 'City of Tagbilaran', '09170001000', '1972-03-14', 31],
            ['BPD-1001', 'PLTCOL R. Hale', 'RH', 'Police Lieutenant Colonel', 4, 'City of Tagbilaran', '09170001001', '1978-08-02', 24],
            ['BPD-1047', 'Det. A. Cross', 'AC', 'Detective', 3, 'Tubigon', '09170001047', '1985-11-21', 14],
            ['BPD-1102', 'PSSg T. Reyes', 'TR', 'Police Staff Sergeant', 2, 'Talibon', '09170001102', '1990-05-09', 9],
            ['BPD-1200', 'Pat. M. Ono', 'MO', 'Patrolman / Patrolwoman', 1, 'Ubay', '09170001200', '1999-01-30', 3],
        ];

        foreach ($officers as [$badge, $name, $initials, $rank, $level, $station, $phone, $born, $years]) {
            User::updateOrCreate(
                ['badge_id' => $badge],
                [
                    'name' => $name, 'initials' => $initials, 'rank' => $rank, 'clearance_level' => $level,
                    'is_active' => true, 'password' => $password, 'station' => $station, 'phone' => $phone,
                    'date_of_birth' => $born, 'years_of_service' => $years, 'address' => $station,
                ]
            );
        }

        if (! config('bpd.seed_password')) {
            $this->command?->info('Demo officers BPD-1000 (Chief Admin), 1001, 1047, 1102 and 1200 created. Password: demo1234 (local only).');
        }
    }
}
