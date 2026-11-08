<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    private function infraction(Subject $subject, string $type, string $date = '2026-02-01'): int
    {
        return $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId($type),
            'occurred_on' => $date,
            'location' => 'Tubigon',
        ])->assertCreated()->json('data.id');
    }

    public function test_the_numbers_behind_the_charts(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $served = $this->infraction($subject, 'burglary');
        $this->infraction($subject, 'burglary');
        $this->infraction($subject, 'fraud');
        $this->patchJson("/api/v1/infractions/{$served}", ['served' => true])->assertOk();

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['helicopters' => 1, 'outcome' => 'APPREHENDED']))->assertCreated();
        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['officers_involved' => 10]))->assertCreated();

        foreach (['Tubigon', 'Tubigon', 'Loboc'] as $place) {
            $this->postJson("/api/v1/subjects/{$subject->id}/sightings", ['district' => $place, 'seen_at' => now()->subHour()->toDateTimeString()])->assertCreated();
        }

        $data = $this->getJson("/api/v1/subjects/{$subject->id}/analytics")->assertOk()->json('data');

        $this->assertSame('burglary', $data['infractions_by_type'][0]['code']);
        $this->assertSame(2, $data['infractions_by_type'][0]['total']);
        $this->assertSame(1, $data['infractions_by_type'][0]['unserved']);

        $severity = collect($data['infractions_by_severity'])->pluck('total', 'severity');
        $this->assertSame(2, $severity['HIGH']);
        $this->assertSame(1, $severity['MEDIUM']);
        $this->assertSame(0, $severity['LOW']);

        $this->assertSame(['served' => 1, 'unserved' => 2], $data['served']);

        // Burglary 120,000 twice and fraud 75,000, plus manhunt bounties of 800 and 1,000.
        $this->assertSame(316_800, collect($data['bounty_timeline'])->last()['cumulative']);

        $this->assertCount(2, $data['manhunt_costs']);
        $this->assertSame(2000, $data['manhunt_costs'][0]['total']);
        $this->assertSame('helicopters', $data['manhunt_costs'][0]['lines'][0]['code']);

        $this->assertSame(['name' => 'Tubigon', 'count' => 2], $data['sightings_by_place'][0]);
        $this->assertSame(3, collect($data['sightings_by_month'])->sum('count'));

        $this->assertSame(33, $data['progress']['infractions_served']);
        $this->assertSame(0, $data['progress']['fines_paid']);
        $this->assertSame(50, $data['progress']['manhunts_apprehended']);
    }

    public function test_an_empty_record_gives_empty_charts_and_zero_progress(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $data = $this->getJson("/api/v1/subjects/{$subject->id}/analytics")->assertOk()->json('data');

        $this->assertSame([], $data['infractions_by_type']);
        $this->assertSame([], $data['bounty_timeline']);
        $this->assertSame([], $data['sightings_by_place']);
        $this->assertSame(['infractions_served' => 0, 'fines_paid' => 0, 'manhunts_apprehended' => 0], $data['progress']);
    }

    public function test_patrol_officers_do_not_get_the_charts(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(1);

        $this->getJson("/api/v1/subjects/{$subject->id}/analytics")->assertForbidden();
    }
}
