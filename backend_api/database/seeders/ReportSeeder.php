<?php

namespace Database\Seeders;

use App\Models\InfractionType;
use App\Models\Municipality;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

/** A few field reports from the demo officers, so the Reports page has something to show. */
class ReportSeeder extends Seeder
{
    public function run(): void
    {
        if (Report::query()->exists()) {
            return;
        }

        $filers = User::query()->whereIn('badge_id', ['BPD-1200', 'BPD-1102', 'BPD-1047'])->get()->keyBy('badge_id');
        $reviewer = User::query()->where('badge_id', 'BPD-1102')->first();
        $types = InfractionType::query()->orderBy('id')->get();
        $towns = Municipality::query()->orderBy('name')->pluck('name');

        if ($filers->isEmpty() || $types->isEmpty() || $towns->isEmpty()) {
            return;
        }

        // filer, type code, narrative, reviewed
        $reports = [
            ['BPD-1200', 'trespassing', 'Two men were seen climbing the fence of a closed warehouse near the port. They ran when the patrol car arrived.', true],
            ['BPD-1200', 'property-damage', 'A shop owner reported broken windows and a damaged roller door after a night disturbance. No suspect was seen.', true],
            ['BPD-1200', 'fraud', 'A resident said a man collected advance payments for motorcycle repairs and then stopped answering calls.', false],
            ['BPD-1102', 'burglary', 'A house was entered while the family was at church. A television and some cash were taken. A neighbour saw a grey motorcycle.', true],
            ['BPD-1102', 'assault', 'A fight broke out outside a bar after closing. One man had a cut lip. The other left before officers arrived.', false],
            ['BPD-1047', 'evading-officers', 'A driver ignored a checkpoint signal and sped away toward the national road. The plate was partly read.', false],
        ];

        foreach ($reports as $index => [$badge, $code, $narrative, $reviewed]) {
            $filer = $filers[$badge] ?? null;

            if ($filer === null) {
                continue;
            }

            Report::create([
                'reference' => 'BPD-R-'.now()->format('Y').'-'.str_pad((string) (100200 + $index), 6, '0', STR_PAD_LEFT),
                'filed_by' => $filer->id,
                'infraction_type_id' => $types->firstWhere('code', $code)?->id,
                'occurred_at' => now()->subDays(2 + $index * 3)->subHours($index + 1),
                'municipality' => $towns[($index * 7) % $towns->count()],
                'narrative' => $narrative,
            ])->forceFill($reviewed && $reviewer ? [
                'status' => Report::REVIEWED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now()->subDays(max(0, 1 + $index)),
            ] : [])->save();
        }
    }
}
