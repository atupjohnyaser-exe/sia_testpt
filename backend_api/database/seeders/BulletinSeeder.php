<?php

namespace Database\Seeders;

use App\Models\Bulletin;
use Illuminate\Database\Seeder;

class BulletinSeeder extends Seeder
{
    public function run(): void
    {
        $bulletins = [
            ['BUL-4840-A', 'South Harbor District: increased foot patrols through the weekend. Report all sightings to dispatch.'],
            ['BUL-4840-B', 'Checkpoint rotation changes at 22:00. Supervisors confirm unit counts with central.'],
            ['BUL-4840-C', 'Records reminder: log every manhunt within 24 hours so cost to state stays accurate.'],
        ];

        foreach ($bulletins as $index => [$ref, $body]) {
            Bulletin::updateOrCreate(
                ['ref' => $ref],
                ['body' => $body, 'is_active' => true, 'published_at' => now()->subHours(3 - $index)]
            );
        }
    }
}
