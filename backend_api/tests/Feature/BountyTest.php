<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class BountyTest extends TestCase
{
    private function addInfraction(Subject $subject, string $type): int
    {
        return $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId($type),
            'occurred_on' => '2026-02-01',
            'location' => 'Civic District',
        ])->assertCreated()->json('data.id');
    }

    public function test_an_unserved_infraction_adds_bounty_fines_and_threat(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->addInfraction($subject, 'armed-robbery');

        $subject->refresh();
        $this->assertSame(300_000, $subject->bounty);
        $this->assertSame(15_000, $subject->fines_due);
        $this->assertSame(1, $subject->total_infractions);
        $this->assertSame(1, $subject->unserved_infractions);
        $this->assertSame(3, $subject->threat_level);
    }

    public function test_serving_an_infraction_removes_its_bounty(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $id = $this->addInfraction($subject, 'armed-robbery');

        $this->patchJson("/api/v1/infractions/{$id}", ['served' => true])->assertOk()->assertJsonPath('data.served', true);

        $subject->refresh();
        $this->assertSame(0, $subject->bounty);
        $this->assertSame(0, $subject->unserved_infractions);
        $this->assertSame(1, $subject->total_infractions);
        $this->assertSame(1, $subject->threat_level);
        $this->assertDatabaseHas('audit_logs', ['action' => 'infraction_served', 'subject_id' => $subject->id]);
    }

    public function test_paying_a_fine_moves_it_from_due_to_paid(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $id = $this->addInfraction($subject, 'fraud');

        $this->patchJson("/api/v1/infractions/{$id}", ['fine_paid' => true])->assertOk();

        $subject->refresh();
        $this->assertSame(0, $subject->fines_due);
        $this->assertSame(5_000, $subject->fines_paid);
        $this->assertSame(75_000, $subject->bounty);
    }

    public function test_infraction_fine_and_bounty_are_copied_from_the_type(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $id = $this->addInfraction($subject, 'burglary');

        \App\Models\InfractionType::where('code', 'burglary')->update(['bounty_value' => 1, 'fine_amount' => 1]);

        $this->assertDatabaseHas('infractions', ['id' => $id, 'bounty_value' => 120_000, 'fine_amount' => 7_500]);
    }

    public function test_manhunt_cost_and_bounty_are_calculated_from_the_rates(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        // 10 x 250 + 2 x 2000 + 1 x 1200 = 7700. Bounty is 40 percent of that.
        $response = $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody([
            'officers_involved' => 10,
            'helicopters' => 2,
            'k9_units' => 1,
        ]))->assertCreated();

        $response->assertJsonPath('data.cost_to_state', 7_700)->assertJsonPath('data.bounty_value', 3_080);
        $this->assertMatchesRegularExpression('/^MH-[A-Z0-9]{8}$/', $response->json('data.code'));
        $this->assertCount(3, $response->json('data.cost_breakdown'));

        $subject->refresh();
        $this->assertSame(3_080, $subject->bounty);
        $this->assertSame(7_700, $subject->cost_to_state);
    }

    public function test_infractions_can_be_recorded_during_a_manhunt(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody([
            'infractions' => [
                ['infraction_type_id' => $this->typeId('resisting-arrest')],
                ['infraction_type_id' => $this->typeId('assaulting-officer'), 'location' => 'Old Quarter'],
            ],
        ]))->assertCreated()->assertJsonPath('data.infractions_count', 2);

        $this->assertSame(2, $subject->fresh()->total_infractions);
        $this->assertSame(240_000, $subject->fresh()->bounty);
    }

    public function test_cost_to_state_table_adds_up_all_manhunts(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['helicopters' => 1, 'officers_involved' => 4]))->assertCreated();
        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['helicopters' => 2]))->assertCreated();

        $response = $this->getJson("/api/v1/subjects/{$subject->id}/cost-to-state")->assertOk();
        $lines = collect($response->json('data.lines'))->keyBy('code');

        $this->assertSame(3, $lines['helicopters']['qty']);
        $this->assertSame(6_000, $lines['helicopters']['cost']);
        $this->assertSame(4, $lines['officers_involved']['qty']);
        $this->assertSame(7_000, $response->json('data.total'));
    }

    public function test_changing_a_rate_does_not_rewrite_stored_manhunts(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['helicopters' => 1]))->assertCreated();

        $this->putJson('/api/v1/cost-rates', ['rates' => [['code' => 'helicopters', 'unit_cost' => 9_999]]])->assertOk();

        $this->assertSame(2_000, $subject->fresh()->cost_to_state);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rates_updated']);

        $second = $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['helicopters' => 1]))->json('data.cost_to_state');
        $this->assertSame(9_999, $second);
    }

    public function test_cost_rates_need_supervisor_clearance(): void
    {
        $this->actingAsLevel(3);

        $this->putJson('/api/v1/cost-rates', ['rates' => [['code' => 'helicopters', 'unit_cost' => 1]]])->assertForbidden();
    }

    public function test_a_threat_override_beats_the_calculated_level_and_can_be_removed(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->putJson("/api/v1/subjects/{$subject->id}/threat-override", ['level' => 6, 'reason' => 'Command directive'])
            ->assertOk()
            ->assertJsonPath('data.threat.level', 6)
            ->assertJsonPath('data.threat.overridden', true);

        $this->addInfraction($subject, 'trespassing');
        $this->assertSame(6, $subject->fresh()->threat_level);

        $this->putJson("/api/v1/subjects/{$subject->id}/threat-override", ['level' => null])
            ->assertOk()
            ->assertJsonPath('data.threat.level', 1)
            ->assertJsonPath('data.threat.overridden', false);
    }

    public function test_a_threat_override_needs_a_reason(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->putJson("/api/v1/subjects/{$subject->id}/threat-override", ['level' => 5])->assertStatus(422);
    }

    public function test_infraction_summary_groups_by_type(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $first = $this->addInfraction($subject, 'burglary');
        $this->addInfraction($subject, 'burglary');
        $this->addInfraction($subject, 'fraud');
        $this->patchJson("/api/v1/infractions/{$first}", ['served' => true])->assertOk();

        $types = collect($this->getJson("/api/v1/subjects/{$subject->id}/infraction-summary")->assertOk()->json('data.types'))->keyBy('code');

        $this->assertSame(2, $types['burglary']['total']);
        $this->assertSame(1, $types['burglary']['unserved']);
        $this->assertSame(1, $types['fraud']['total']);
    }

    public function test_sightings_are_grouped_by_district_and_set_the_last_known_location(): void
    {
        $subject = Subject::factory()->create(['last_known_location' => 'Old Quarter']);
        $this->actingAsLevel(2);

        foreach ([['East Rail Yards', 5], ['East Rail Yards', 3], ['North Precinct', 1]] as [$district, $daysAgo]) {
            $this->postJson("/api/v1/subjects/{$subject->id}/sightings", [
                'district' => $district,
                'seen_at' => now()->subDays($daysAgo)->toDateTimeString(),
            ])->assertCreated();
        }

        $data = $this->getJson("/api/v1/subjects/{$subject->id}/locations")->assertOk()->json('data');

        $this->assertSame('North Precinct', $data['last_known_location']);
        $this->assertSame('North Precinct', $data['districts'][0]['district']);
        $this->assertSame(2, collect($data['districts'])->firstWhere('district', 'East Rail Yards')['sightings']);
    }
}
