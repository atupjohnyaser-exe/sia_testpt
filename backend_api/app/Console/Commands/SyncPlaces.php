<?php

namespace App\Console\Commands;

use App\Models\Barangay;
use App\Models\Municipality;
use App\Services\PlaceService;
use App\Services\PsgcClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncPlaces extends Command
{
    protected $signature = 'bpd:sync-places
        {--skip-barangays : Only load the municipalities}
        {--only= : Only load the barangays of this municipality, by name}';

    protected $description = 'Load the real municipalities and barangays of Bohol from PSGC Cloud';

    public function handle(PsgcClient $psgc, PlaceService $places): int
    {
        $this->info('Asking PSGC Cloud for the places of '.config('bpd.psgc.province').'...');

        try {
            $rows = $psgc->municipalities();
        } catch (Throwable $error) {
            $this->error('Could not reach PSGC Cloud: '.$error->getMessage());
            $this->line('Check your internet connection. The built-in list of municipalities still works in the meantime.');

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->error('PSGC Cloud returned no municipalities. Nothing was changed.');

            return self::FAILURE;
        }

        // The built-in list (codes start with FB-) is replaced by the real one.
        DB::transaction(function () use ($rows) {
            Municipality::query()->where('code', 'like', 'FB-%')->delete();

            foreach ($rows as $row) {
                Municipality::updateOrCreate(['code' => $row['code']], ['name' => $row['name'], 'type' => $row['type']]);
            }
        });

        $places->forget();

        $this->info(count($rows).' places loaded ('.collect($rows)->where('type', 'City')->count().' of them a city).');

        if ($this->option('skip-barangays')) {
            return self::SUCCESS;
        }

        $targets = Municipality::query()->orderBy('name')->when(
            $this->option('only'),
            fn ($query, string $name) => $query->where('name', $name)
        )->get();

        $this->output->progressStart($targets->count());
        $failed = [];
        $total = 0;

        foreach ($targets as $municipality) {
            try {
                $barangays = $psgc->barangays($municipality->code);

                DB::transaction(function () use ($municipality, $barangays) {
                    foreach ($barangays as $barangay) {
                        Barangay::updateOrCreate(
                            ['code' => $barangay['code']],
                            ['municipality_id' => $municipality->id, 'name' => $barangay['name']]
                        );
                    }
                });

                $total += count($barangays);
            } catch (Throwable) {
                $failed[] = $municipality->name;
            }

            $this->output->progressAdvance();

            // PSGC Cloud is free and shared. Go gently.
            usleep(150000);
        }

        $this->output->progressFinish();
        $this->info("{$total} barangays loaded.");

        if ($failed !== []) {
            $this->warn('These could not be loaded, run the command again: '.implode(', ', $failed));
        }

        return self::SUCCESS;
    }
}
