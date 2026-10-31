<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Subject;
use Tests\TestCase;

class CaseFileTest extends TestCase
{
    public function test_a_detective_gets_the_whole_file_and_it_is_audited(): void
    {
        $subject = Subject::factory()->create(['name' => 'Mara Voss']);
        $officer = $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId('burglary'),
            'occurred_on' => '2026-01-10',
            'location' => 'Civic District',
        ])->assertCreated();
        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['helicopters' => 1]))->assertCreated();
        $this->postJson("/api/v1/subjects/{$subject->id}/sightings", [
            'district' => 'Old Quarter',
            'seen_at' => now()->subHour()->toDateTimeString(),
        ])->assertCreated();

        $this->postJson("/api/v1/subjects/{$subject->id}/case-file")
            ->assertOk()
            ->assertJsonPath('data.subject.name', 'Mara Voss')
            ->assertJsonPath('data.generated_by.badge_id', $officer->badge_id)
            ->assertJsonCount(1, 'data.infractions')
            ->assertJsonCount(1, 'data.manhunts')
            ->assertJsonCount(1, 'data.locations')
            ->assertJsonPath('data.cost_to_state.total', 2_000)
            ->assertJsonStructure(['data' => ['status_history' => [['from', 'to', 'reason']]]]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'case_file_generated', 'subject_id' => $subject->id, 'user_id' => $officer->id]);
    }

    public function test_every_generation_is_logged(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/case-file")->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/case-file")->assertOk();

        $this->assertSame(2, AuditLog::where('action', 'case_file_generated')->count());
    }

    public function test_an_officer_cannot_generate_a_case_file(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/case-file")->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'case_file_generated']);
    }

    public function test_cost_rates_include_the_bounty_multiplier(): void
    {
        $this->actingAsLevel(1);

        $this->getJson('/api/v1/cost-rates')->assertOk()->assertJsonPath('meta.bounty_multiplier', 0.4)->assertJsonPath('data.0.code', 'officers_involved');
    }

    public function test_infraction_dates_may_be_up_to_tomorrow_but_no_further(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $body = fn (string $date) => [
            'infraction_type_id' => $this->typeId('fraud'),
            'occurred_on' => $date,
            'location' => 'Civic District',
        ];

        $this->postJson("/api/v1/subjects/{$subject->id}/infractions", $body(now()->addDay()->toDateString()))->assertCreated();
        $this->postJson("/api/v1/subjects/{$subject->id}/infractions", $body(now()->addDays(3)->toDateString()))->assertStatus(422);
    }
}
