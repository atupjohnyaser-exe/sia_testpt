<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\CostRateSeeder;
use Database\Seeders\InfractionTypeSeeder;
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
}
