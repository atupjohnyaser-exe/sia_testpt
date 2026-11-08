<?php

namespace Database\Seeders;

use App\Models\Bulletin;
use Illuminate\Database\Seeder;

class BulletinSeeder extends Seeder
{
    public function run(): void
    {
        $bulletins = [
            ['BUL-4840-A', 'Tubigon, Calape and Loon: increased foot patrols through the weekend. Report all sightings to dispatch.'],
            ['BUL-4840-B', 'Checkpoint rotation changes at 22:00. Supervisors confirm unit counts with the provincial command.'],
            ['BUL-4840-C', 'Records reminder: file every field report within 24 hours so cost to state stays accurate.'],
        ];

        foreach ($bulletins as $index => [$ref, $body]) {
            Bulletin::updateOrCreate(
                ['ref' => $ref],
                ['body' => $body, 'is_active' => true, 'published_at' => now()->subHours(3 - $index)]
            );
        }
    }
}
