<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InfractionTypeSeeder::class,
            CostRateSeeder::class,
            OfficerSeeder::class,
            BulletinSeeder::class,
        ]);

        // Fictional sample people. Never loaded in production.
        if (! app()->isProduction()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
