<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\CostRateSeeder;
use Database\Seeders\InfractionTypeSeeder;
use Database\Seeders\PlaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InfractionTypeSeeder::class);
        $this->seed(CostRateSeeder::class);
        $this->seed(PlaceSeeder::class);

        // The limit on open applications would trip tests that send many. One test turns it back on.
        config(['bpd.application.per_hour' => 1000]);
    }

    /** Creates an officer with this clearance and signs them in for the next requests. */
    protected function actingAsLevel(int $level): User
    {
        $user = User::factory()->clearance($level)->create();

        Sanctum::actingAs($user);

        return $user;
    }

    protected function typeId(string $code): int
    {
        return \App\Models\InfractionType::where('code', $code)->value('id');
    }

    /** A valid body for POST /subjects/{id}/manhunts. */
    protected function manhuntBody(array $overrides = []): array
    {
        return array_merge([
            'started_at' => now()->subHour()->toDateTimeString(),
            'duration_seconds' => 1500,
            'outcome' => 'EVADED',
            'district' => 'Civic District',
        ], $overrides);
    }

    /**
     * A real PNG built by hand, so the upload tests do not need the GD extension.
     */
    protected function pngBytes(int $width = 120, int $height = 120): string
    {
        $row = "\x00".str_repeat("\x20\x80\x40", $width);
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress(str_repeat($row, $height)))
            .$chunk('IEND', '');
    }

    /** A valid body for POST /access-requests. */
    protected function application(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Juan Dela Cruz',
            'rank' => 'Detective',
            'date_of_birth' => now()->subYears(35)->toDateString(),
            'phone' => '09171234567',
            'email' => 'juan@example.com',
            'station' => 'Tubigon',
            'address_municipality' => 'Tubigon',
            'address_street' => 'Purok 3',
            'years_of_service' => 10,
            'justification' => 'Assigned to the investigation unit and need access to case records.',
            'declaration' => true,
        ], $overrides);
    }

    /** A valid body for POST /reports. */
    protected function reportBody(array $overrides = []): array
    {
        return array_merge([
            'infraction_type_id' => $this->typeId('burglary'),
            'occurred_at' => now()->subHours(3)->toDateTimeString(),
            'municipality' => 'Tubigon',
            'street' => 'National Road',
            'person_name' => 'Unknown male',
            'narrative' => 'A house was entered while the family was away and a television was taken.',
        ], $overrides);
    }
}
