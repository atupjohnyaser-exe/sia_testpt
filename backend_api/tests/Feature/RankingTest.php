<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class RankingTest extends TestCase
{
    public function test_single_scope_ranks_individual_manhunts(): void
    {
        $slow = Subject::factory()->create(['name' => 'Short Runner']);
        $long = Subject::factory()->create(['name' => 'Long Runner']);
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$slow->id}/manhunts", $this->manhuntBody(['duration_seconds' => 600]))->assertCreated();
        $this->postJson("/api/v1/subjects/{$long->id}/manhunts", $this->manhuntBody(['duration_seconds' => 2600]))->assertCreated();

        $this->getJson('/api/v1/rankings?scope=single&category=length')
            ->assertOk()
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.0.subject.id', $long->id)
            ->assertJsonPath('data.0.value', 2600)
            ->assertJsonPath('data.1.subject.id', $slow->id);
    }

    public function test_lifetime_scope_adds_up_manhunts_per_subject(): void
    {
        $many = Subject::factory()->create();
        $one = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$many->id}/manhunts", $this->manhuntBody(['helicopters' => 1]))->assertCreated();
        $this->postJson("/api/v1/subjects/{$many->id}/manhunts", $this->manhuntBody(['helicopters' => 2]))->assertCreated();
        $this->postJson("/api/v1/subjects/{$one->id}/manhunts", $this->manhuntBody(['helicopters' => 2]))->assertCreated();

        $this->getJson('/api/v1/rankings?scope=lifetime&category=helicopters')
            ->assertOk()
            ->assertJsonPath('data.0.subject.id', $many->id)
            ->assertJsonPath('data.0.value', 3)
            ->assertJsonPath('data.1.value', 2);
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $this->actingAsLevel(1);

        $this->getJson('/api/v1/rankings?category=bounty;drop')->assertStatus(422);
    }

    public function test_archived_subjects_do_not_appear(): void
    {
        $gone = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->postJson("/api/v1/subjects/{$gone->id}/manhunts", $this->manhuntBody())->assertCreated();
        $this->deleteJson("/api/v1/subjects/{$gone->id}")->assertOk();

        $this->assertSame([], $this->getJson('/api/v1/rankings?scope=single&category=length')->assertOk()->json('data'));
    }
}
