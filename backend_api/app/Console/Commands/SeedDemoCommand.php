<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Bulletin;
use App\Models\CaseNote;
use App\Models\Offense;
use App\Models\Subject;
use App\Models\User;
use App\Services\DummyJsonService;
use App\Services\OffenseClassifier;
use App\Services\RiskScoreService;
use App\Services\UkPoliceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SeedDemoCommand extends Command
{
    protected $signature = 'rpd:seed-demo {--fresh : Replace all demo records before seeding}';
    protected $description = 'Seed the fictional Rockport Police Department training dashboard';
    private array $districts = ['South Harbor District', 'East Rail Yards', 'Civic District', 'North Precinct', 'Central Detention'];
    private array $aliasWords = ['Night Glass', 'The Switch', 'Grey Fox', 'Cinder', 'Static', 'Ember', 'Blue Ash', 'Glass Finch'];

    public function handle(DummyJsonService $people, UkPoliceService $police, RiskScoreService $risk, OffenseClassifier $classifier): int
    {
        if ($this->option('fresh')) {
            DB::table('personal_access_tokens')->delete(); DB::table('audit_logs')->delete();
            DB::table('case_notes')->delete(); DB::table('offenses')->delete(); DB::table('subjects')->delete();
            DB::table('bulletins')->delete(); DB::table('users')->where('badge_id', '!=', 'RP-1047')->delete();
        }

        DB::transaction(function () use ($people, $police, $risk, $classifier) {
            $officer = User::updateOrCreate(['badge_id' => 'RP-1047'], [
                'name' => 'Det. A. Cross', 'initials' => 'AC', 'rank' => 'Detective',
                'clearance_level' => 3, 'password' => Hash::make('demo1234'),
            ]);

            $fixed = [
                ['case_id' => 'RP-0840-AX', 'name' => 'Mara Voss', 'alias' => 'The Switch', 'age' => 34, 'status' => 'WANTED', 'classification' => 'Priority Alpha', 'last_known_location' => 'South Harbor District', 'height' => "5'8\"", 'eye_color' => 'Hazel', 'hair_color' => 'Black', 'distinguishing_features' => 'Geometric tattoo behind left ear', 'risk_score' => 86, 'risk_override' => true, 'officer_advisory' => 'Subject is methodical and surveillance-aware. Approach with caution. Known to change appearance and travel under forged credentials.', 'advisory_reviewed_by' => $officer->id, 'advisory_reviewed_at' => '2025-05-14 09:42:00'],
                ['case_id' => 'RP-1153-KL', 'name' => 'Elias Kane', 'alias' => 'Grey Fox', 'age' => 39, 'status' => 'IN_CUSTODY', 'classification' => 'Priority Beta', 'last_known_location' => 'Central Detention', 'risk_score' => 72, 'risk_override' => true],
                ['case_id' => 'RP-2297-QN', 'name' => 'Soren Vale', 'alias' => 'Night Glass', 'age' => 31, 'status' => 'WANTED', 'classification' => 'Priority Alpha', 'last_known_location' => 'East Rail Yards', 'risk_score' => 91, 'risk_override' => true],
                ['case_id' => 'RP-4001-CR', 'name' => 'Nadia Rourke', 'alias' => 'Cinder', 'age' => 27, 'status' => 'PAROLED', 'classification' => 'Under review', 'last_known_location' => 'Civic District', 'risk_score' => 48, 'risk_override' => true],
            ];
            foreach ($fixed as $data) {
                $subject = Subject::withTrashed()->firstOrNew(['case_id' => $data['case_id']]);
                $subject->fill($data);
                $subject->deleted_at = null;
                $subject->save();
            }
            $mara = Subject::where('case_id', 'RP-0840-AX')->firstOrFail();
            $this->seedMara($mara, $officer);

            $crimes = $police->crimes();
            $users = $people->users();
            if ($users === []) $users = $this->fallback('fallback_subjects.json');
            if ($crimes === []) $crimes = $this->fallback('fallback_crimes.json');
            if ($users === [] || $crimes === []) Log::warning('Demo seeder used fallback fixtures for unavailable public APIs.');

            foreach (array_slice($users, 0, 20) as $index => $person) {
                if (! isset($person['firstName'], $person['lastName'])) continue;
                $name = trim($person['firstName'].' '.$person['lastName']);
                $suffix = Str::upper(substr(preg_replace('/[^A-Za-z]/', '', $person['lastName']), 0, 2) ?: 'ZZ');
                $caseId = 'RP-'.str_pad((string) (1000 + (int) ($person['id'] ?? $index)), 4, '0', STR_PAD_LEFT).'-'.$suffix;
                if (Subject::withTrashed()->where('case_id', $caseId)->exists()) continue;
                $city = $person['address']['city'] ?? '';
                $district = $this->districts[abs(crc32(strtolower($city))) % count($this->districts)];
                $heightCm = (float) ($person['height'] ?? 173);
                $inches = (int) round($heightCm / 2.54); $height = intdiv($inches, 12)."'".($inches % 12).'"';
                $subject = Subject::create([
                    'case_id' => $caseId, 'name' => $name, 'alias' => $this->aliasWords[$index % count($this->aliasWords)],
                    'age' => $person['age'] ?? null, 'status' => 'WANTED', 'classification' => 'Under review',
                    'last_known_location' => $district, 'height' => $height, 'eye_color' => ucfirst($person['eyeColor'] ?? ''),
                    'hair_color' => ucfirst($person['hair']['color'] ?? ''), 'photo_url' => $person['image'] ?? null,
                    'risk_score' => 15, 'risk_override' => false,
                ]);
                $picked = collect($crimes)->slice(($index * 3) % max(1, count($crimes)), 1 + ($index % 4));
                foreach ($picked as $crime) {
                    $category = $crime['category'] ?? 'other-crime';
                    $outcome = $crime['outcome_status']['category'] ?? 'Under investigation';
                    $date = $crime['month'] ?? now()->subMonths($index + 1)->format('Y-m');
                    $subject->offenses()->create([
                        'category' => $category, 'charge' => $classifier->charge($category),
                        'occurred_on' => $date.'-'.str_pad((string) random_int(1, 28), 2, '0', STR_PAD_LEFT),
                        'location' => $this->districts[abs(crc32($crime['location']['street']['name'] ?? $city)) % count($this->districts)],
                        'disposition' => $outcome, 'status_effect' => $classifier->statusEffect($outcome), 'severity' => $risk->severity($category),
                    ]);
                }
                $risk->recalculate($subject);
            }

            foreach ([
                ['body' => 'Priority Alpha protocol remains active for South Harbor and East Rail districts.', 'ref' => 'OPS-25-114'],
                ['body' => 'Records review window is open. Confirm all outstanding warrants before shift change.', 'ref' => 'OPS-25-115'],
                ['body' => 'Central Detention intake records synchronized with the case index.', 'ref' => 'OPS-25-116'],
            ] as $bulletin) Bulletin::firstOrCreate(['ref' => $bulletin['ref']], $bulletin + ['published_at' => now(), 'is_active' => true]);
        });

        $this->info('Fictional RPD demo records are ready.');
        return self::SUCCESS;
    }

    private function seedMara(Subject $mara, User $officer): void
    {
        $entries = [
            ['2025-03-18', 'violent-crime', 'Organized fraud', 'South Harbor', 'Active warrant', 'HIGH'],
            ['2024-11-02', 'violent-crime', 'Identity theft', 'Civic District', 'Unresolved', 'HIGH'],
            ['2023-06-14', 'other-crime', 'Obstruction', 'North Precinct', 'Case dismissed', 'LOW'],
        ];
        foreach ($entries as [$date, $category, $charge, $location, $disposition, $severity]) {
            Offense::firstOrCreate(['subject_id' => $mara->id, 'occurred_on' => $date, 'charge' => $charge], [
                'category' => $category, 'location' => $location, 'disposition' => $disposition,
                'status_effect' => 'WANTED', 'severity' => $severity,
            ]);
        }
        $note = CaseNote::firstOrCreate(['subject_id' => $mara->id, 'user_id' => $officer->id, 'body' => $mara->officer_advisory], ['access_level' => 3]);
        $note->forceFill(['created_at' => '2025-05-14 09:42:00', 'updated_at' => '2025-05-14 09:42:00'])->save();
    }

    private function fallback(string $file): array
    {
        $path = database_path('seeders/data/'.$file);
        if (! is_file($path)) return [];
        try { return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) ?: []; }
        catch (Throwable $exception) { Log::warning('Invalid fallback seeder data.', ['file' => $file, 'message' => $exception->getMessage()]); return []; }
    }
}
