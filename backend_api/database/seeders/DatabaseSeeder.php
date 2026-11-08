<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlaceSeeder::class,
            InfractionTypeSeeder::class,
            CostRateSeeder::class,
            OfficerSeeder::class,
            BulletinSeeder::class,
        ]);

        // Fictional sample people and reports. Never loaded in production.
        if (! app()->isProduction()) {
            $this->call([DemoDataSeeder::class, ReportSeeder::class]);
        }
    }
}
