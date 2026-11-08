<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Services\PlaceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A short list of Bohol's municipalities so every dropdown works on a fresh install.
 * The real list, with codes and all 1,109 barangays, comes from: php artisan bpd:sync-places
 * Skipped when places are already there.
 */
class PlaceSeeder extends Seeder
{
    public const MUNICIPALITIES = [
        'Alburquerque', 'Alicia', 'Anda', 'Antequera', 'Baclayon', 'Balilihan', 'Batuan', 'Bien Unido', 'Bilar',
        'Buenavista', 'Calape', 'Candijay', 'Carmen', 'Catigbian', 'Clarin', 'Corella', 'Cortes', 'Dagohoy', 'Danao',
        'Dauis', 'Dimiao', 'Duero', 'Garcia Hernandez', 'Getafe', 'Guindulman', 'Inabanga', 'Jagna', 'Lila', 'Loay',
        'Loboc', 'Loon', 'Mabini', 'Maribojoc', 'Panglao', 'Pilar', 'President Carlos P. Garcia', 'Sagbayan',
        'San Isidro', 'San Miguel', 'Sevilla', 'Sierra Bullones', 'Sikatuna', 'Talibon', 'Trinidad', 'Tubigon',
        'Ubay', 'Valencia',
    ];

    public function run(): void
    {
        if (Municipality::query()->exists()) {
            return;
        }

        foreach (self::MUNICIPALITIES as $name) {
            Municipality::create(['code' => 'FB-'.Str::slug($name), 'name' => $name, 'type' => 'Municipality']);
        }

        Municipality::create(['code' => 'FB-city-of-tagbilaran', 'name' => 'City of Tagbilaran', 'type' => 'City']);

        app(PlaceService::class)->forget();

        $this->command?->info('Loaded '.(count(self::MUNICIPALITIES) + 1).' Bohol places. Run php artisan bpd:sync-places for the real codes and barangays.');
    }
}
