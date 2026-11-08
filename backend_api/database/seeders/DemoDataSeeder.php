<?php

namespace Database\Seeders;

use App\Enums\SubjectStatus;
use App\Models\Barangay;
use App\Models\CaseNote;
use App\Models\InfractionType;
use App\Models\Municipality;
use App\Models\Sighting;
use App\Models\Subject;
use App\Models\User;
use App\Services\InfractionService;
use App\Services\ManhuntService;
use App\Services\StatusService;
use App\Services\SubjectService;
use App\Services\SubjectStatsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Fictional people only. No outside services are called, so this works offline.
 * Everything goes through the same services as the API, so the numbers on each
 * record are consistent with its infractions and manhunts.
 */
class DemoDataSeeder extends Seeder
{
    /** Real municipalities of Bohol, each with its real barangays when bpd:sync-places has been run. */
    private Collection $places;

    private array $dispositions = ['Warrant issued', 'Awaiting hearing', 'Fine unpaid', 'Referred to prosecutor'];

    private array $firstNames = ['Jonas', 'Priya', 'Dmitri', 'Lena', 'Marcus', 'Ayumi', 'Tobias', 'Camila', 'Rowan', 'Isla', 'Felix', 'Noor', 'Gideon', 'Mina', 'Arlo', 'Selma'];

    private array $lastNames = ['Hargrove', 'Okafor', 'Lindqvist', 'Moreau', 'Castellan', 'Brandt', 'Ibarra', 'Whitlock', 'Nakamura', 'Adeyemi', 'Fontaine', 'Kowalski', 'Mercer', 'Dalgaard', 'Pryce', 'Valdez'];

    private array $aliases = ['Ghost', 'Rook', 'Sparrow', 'Tin Man', 'Velvet', 'Cutter', 'Moth', 'Anchor', 'Static', 'Lantern', 'Brass', 'Hollow', 'Wren', 'Smoke', 'Quill', 'Marrow'];

    public function run(
        SubjectService $subjects,
        InfractionService $infractions,
        ManhuntService $manhunts,
        StatusService $status,
        SubjectStatsService $stats,
    ): void {
        if (Subject::withTrashed()->exists()) {
            $this->command?->warn('Subjects already exist, skipping demo data.');

            return;
        }

        $officer = User::where('badge_id', 'BPD-1047')->first();

        if ($officer === null) {
            $this->command?->warn('Officer BPD-1047 not found, skipping demo data.');

            return;
        }

        mt_srand(4840);
        $types = InfractionType::all()->values();
        $this->places = $this->loadPlaces();

        if ($this->places->isEmpty()) {
            $this->call(PlaceSeeder::class);
            $this->places = $this->loadPlaces();
        }

        $fixed = [
            ['name' => 'Mara Voss', 'alias' => 'The Switch', 'age' => 34, 'last_known_location' => '@Tubigon', 'height' => "5'8\"", 'eye_color' => 'Hazel', 'hair_color' => 'Black', 'distinguishing_features' => 'Geometric tattoo behind left ear', 'officer_advisory' => 'Methodical and surveillance aware. Known to change appearance and travel under forged credentials. Request backup before contact.', 'infractions' => 6, 'manhunts' => 4],
            ['name' => 'Elias Kane', 'alias' => 'Grey Fox', 'age' => 39, 'last_known_location' => '@Loboc', 'height' => "6'1\"", 'eye_color' => 'Grey', 'hair_color' => 'Brown', 'distinguishing_features' => 'Scar across right eyebrow', 'officer_advisory' => 'Often seen with associates. Avoids main roads and moves on foot through rail yards.', 'infractions' => 4, 'manhunts' => 2],
            ['name' => 'Soren Vale', 'alias' => 'Night Glass', 'age' => 31, 'last_known_location' => '@Talibon', 'height' => "5'11\"", 'eye_color' => 'Blue', 'hair_color' => 'Blond', 'distinguishing_features' => null, 'officer_advisory' => 'Escalates quickly when cornered. Do not approach alone.', 'infractions' => 5, 'manhunts' => 3],
            ['name' => 'Nadia Rourke', 'alias' => 'Cinder', 'age' => 27, 'last_known_location' => '@City of Tagbilaran', 'height' => "5'5\"", 'eye_color' => 'Green', 'hair_color' => 'Red', 'distinguishing_features' => 'Burn mark on left forearm', 'officer_advisory' => 'Linked to several arson reports. Last seen near the civic archive.', 'infractions' => 3, 'manhunts' => 1],
        ];

        $people = $fixed;

        foreach ($this->firstNames as $i => $first) {
            $people[] = [
                'name' => $first.' '.$this->lastNames[($i * 5 + 3) % count($this->lastNames)],
                'alias' => $this->aliases[$i % count($this->aliases)],
                'age' => mt_rand(19, 58),
                'last_known_location' => $this->location(),
                'height' => mt_rand(5, 6)."'".mt_rand(0, 11).'"',
                'eye_color' => ['Brown', 'Blue', 'Green', 'Hazel', 'Grey'][mt_rand(0, 4)],
                'hair_color' => ['Black', 'Brown', 'Blond', 'Red', 'Grey'][mt_rand(0, 4)],
                'distinguishing_features' => null,
                'officer_advisory' => null,
                'infractions' => mt_rand(1, 5),
                'manhunts' => mt_rand(0, 3),
            ];
        }

        foreach ($people as $person) {
            $infractionCount = $person['infractions'];
            $manhuntCount = $person['manhunts'];
            unset($person['infractions'], $person['manhunts']);

            if (str_starts_with((string) $person['last_known_location'], '@')) {
                $person['last_known_location'] = $this->location(substr($person['last_known_location'], 1));
            }

            $subject = $subjects->create($person, SubjectStatus::Wanted, $officer);

            for ($i = 0; $i < $infractionCount; $i++) {
                $infraction = $infractions->record($subject, $types[mt_rand(0, $types->count() - 1)], [
                    'occurred_on' => now()->subDays(mt_rand(10, 700))->toDateString(),
                    'location' => $this->location(),
                    'disposition' => $this->dispositions[mt_rand(0, count($this->dispositions) - 1)],
                ], $officer, null, false);

                if (mt_rand(1, 100) <= 25) {
                    $infractions->update($infraction, ['served' => true], $officer);
                }
            }

            for ($i = 0; $i < $manhuntCount; $i++) {
                $manhunts->log($subject, [
                    'started_at' => now()->subDays(max(1, 300 - $i * 70 - mt_rand(0, 25)))->subMinutes(mt_rand(0, 600)),
                    'duration_seconds' => mt_rand(300, 3600),
                    'outcome' => mt_rand(1, 100) <= 70 ? 'EVADED' : 'APPREHENDED',
                    'district' => $this->town(),
                    'officers_involved' => mt_rand(4, 60),
                    'officers_injured' => mt_rand(0, 4),
                    'units_stood_down' => mt_rand(0, 8),
                    'checkpoints' => mt_rand(0, 6),
                    'k9_units' => mt_rand(0, 3),
                    'helicopters' => mt_rand(0, 3),
                    'property_damage_incidents' => mt_rand(0, 15),
                    'insurance_claims' => mt_rand(0, 10),
                ], $officer);
            }

            for ($i = 0, $sightings = mt_rand(1, 5); $i < $sightings; $i++) {
                $spot = $this->spot();

                Sighting::create([
                    'subject_id' => $subject->id,
                    'district' => $spot['municipality'],
                    'address' => $spot['barangay'] ? 'Brgy. '.$spot['barangay'] : null,
                    'seen_at' => now()->subDays(mt_rand(0, 90))->subHours(mt_rand(0, 23)),
                    'source' => ['patrol', 'tip', 'camera', 'informant'][mt_rand(0, 3)],
                    'reported_by' => $officer->id,
                ]);
            }

            // A little variety in the final status.
            $roll = mt_rand(1, 100);

            if ($roll <= 10 && $subject->status !== SubjectStatus::Paroled) {
                $status->change($subject, SubjectStatus::Paroled, 'Released on parole', $officer);
            } elseif ($roll <= 16 && $subject->status !== SubjectStatus::Cleared) {
                $status->change($subject, SubjectStatus::Cleared, 'Charges dismissed', $officer);
            }

            $stats->refresh($subject);
        }

        $mara = Subject::where('name', 'Mara Voss')->first();

        if ($mara !== null) {
            CaseNote::create([
                'subject_id' => $mara->id,
                'user_id' => $officer->id,
                'body' => 'Informant places her near the harbor warehouses on weekday evenings. Needs confirmation before a unit is sent.',
                'access_level' => 3,
            ]);
        }

        $this->command?->info(Subject::count().' demo records created.');
    }

    /** @return Collection<int,array{municipality:string,barangays:array<int,string>}> */
    private function loadPlaces(): Collection
    {
        $barangays = Barangay::query()->get(['municipality_id', 'name'])->groupBy('municipality_id');

        return Municipality::query()->orderBy('name')->get(['id', 'name'])->map(fn (Municipality $municipality) => [
            'municipality' => $municipality->name,
            'barangays' => ($barangays[$municipality->id] ?? collect())->pluck('name')->values()->all(),
        ])->values();
    }

    /** One place, picked the same way every run. A barangay is included once those have been synced. */
    private function spot(?string $municipality = null): array
    {
        $place = $municipality !== null
            ? ($this->places->firstWhere('municipality', $municipality) ?? $this->places[mt_rand(0, $this->places->count() - 1)])
            : $this->places[mt_rand(0, $this->places->count() - 1)];

        $barangays = $place['barangays'];

        return [
            'municipality' => $place['municipality'],
            'barangay' => $barangays === [] ? null : $barangays[mt_rand(0, count($barangays) - 1)],
        ];
    }

    private function town(): string
    {
        return $this->spot()['municipality'];
    }

    /** Text like "Brgy. Poblacion, Tubigon", or just "Tubigon" when barangays are not loaded. */
    private function location(?string $municipality = null): string
    {
        $spot = $this->spot($municipality);

        return $spot['barangay'] ? 'Brgy. '.$spot['barangay'].', '.$spot['municipality'] : $spot['municipality'];
    }
}
