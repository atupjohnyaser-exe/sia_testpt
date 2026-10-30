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
}
