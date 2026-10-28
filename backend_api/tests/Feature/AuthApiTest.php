<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_returns_a_bearer_token_and_officer(): void
    {
        config(['app.demo_mode' => true]);
        $this->postJson('/api/auth/login', ['badge_id' => 'RP-1047', 'access_key' => 'demo1234'])
            ->assertOk()->assertJsonStructure(['token', 'officer' => ['badge_id', 'name', 'clearance_level']]);
    }
}
