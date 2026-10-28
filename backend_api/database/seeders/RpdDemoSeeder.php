<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class RpdDemoSeeder extends Seeder
{
    public function run(): void { Artisan::call('rpd:seed-demo'); }
}
