<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private function officer(): User
    {
        return User::factory()->create(['badge_id' => 'RP-9001', 'password' => 'correct-horse-battery']);
    }

    public function test_login_returns_a_token_that_expires(): void
    {
        $this->officer();

        $response = $this->postJson('/api/v1/auth/login', ['badge_id' => 'rp-9001', 'access_key' => 'correct-horse-battery']);

        $response->assertOk()->assertJsonStructure(['token', 'expires_at', 'officer' => ['id', 'badge_id', 'clearance_level']]);
        $this->assertNotNull(PersonalAccessToken::first()->expires_at);
    }

    public function test_wrong_key_is_rejected_and_logged(): void
    {
        $this->officer();

        $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-9001', 'access_key' => 'nope'])->assertStatus(401);

        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed']);
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_demo_mode_does_not_bypass_the_key_of_an_existing_officer(): void
    {
        config(['rockport.demo_mode' => true]);
        $this->officer();

        $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-9001', 'access_key' => 'guess'])->assertStatus(401);
    }

    public function test_unknown_badge_is_rejected_when_demo_mode_is_off(): void
    {
        $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-0000', 'access_key' => 'anything'])->assertStatus(401);

        $this->assertDatabaseMissing('users', ['badge_id' => 'RP-0000']);
    }

    public function test_demo_mode_creates_a_low_clearance_officer(): void
    {
        config(['rockport.demo_mode' => true]);

        $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-5555', 'access_key' => 'anything-goes'])->assertOk();

        $this->assertDatabaseHas('users', ['badge_id' => 'RP-5555', 'clearance_level' => 2]);
    }

    public function test_deactivated_officer_cannot_log_in(): void
    {
        User::factory()->create(['badge_id' => 'RP-9002', 'password' => 'correct-horse-battery', 'is_active' => false]);

        $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-9002', 'access_key' => 'correct-horse-battery'])->assertStatus(401);
    }

    public function test_logout_revokes_the_token(): void
    {
        $this->officer();

        $token = $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-9001', 'access_key' => 'correct-horse-battery'])->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->getJson('/api/v1/subjects')->assertStatus(401);
    }
}
