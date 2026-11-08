<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Tests\TestCase;

class ReportTest extends TestCase
{
    private function fileAs(User $user, array $overrides = []): Report
    {
        return Report::create($this->reportBody($overrides) + [
            'reference' => 'BPD-R-2026-'.random_int(100000, 999999),
            'filed_by' => $user->id,
        ]);
    }

    public function test_a_patrol_officer_files_a_report(): void
    {
        $officer = $this->actingAsLevel(1);

        $response = $this->postJson('/api/v1/reports', $this->reportBody())->assertCreated();

        $this->assertMatchesRegularExpression('/^BPD-R-\d{4}-\d{6}$/', $response->json('data.reference'));
        $response->assertJsonPath('data.status', 'FILED')->assertJsonPath('data.filed_by.id', $officer->id)->assertJsonPath('data.can_edit', true);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_filed', 'user_id' => $officer->id]);
    }

    public function test_a_patrol_officer_sees_only_their_own_reports(): void
    {
        $other = User::factory()->clearance(1)->create();
        $this->fileAs($other);

        $me = $this->actingAsLevel(1);
        $mine = $this->fileAs($me);

        $ids = collect($this->getJson('/api/v1/reports')->assertOk()->json('data'))->pluck('id');

        $this->assertSame([$mine->id], $ids->all());
    }

    public function test_a_patrol_officer_cannot_open_someone_elses_report(): void
    {
        $other = User::factory()->clearance(1)->create();
        $theirs = $this->fileAs($other);

        $me = $this->actingAsLevel(1);
        $mine = $this->fileAs($me);

        $this->getJson("/api/v1/reports/{$theirs->id}")->assertNotFound();
        $this->getJson("/api/v1/reports/{$mine->id}")->assertOk();
    }

    public function test_a_patrol_officer_edits_their_own_report_until_it_is_reviewed(): void
    {
        $me = $this->actingAsLevel(1);
        $report = $this->fileAs($me);

        $this->putJson("/api/v1/reports/{$report->id}", $this->reportBody(['narrative' => 'A corrected account of what happened that night at the house.']))
            ->assertOk()
            ->assertJsonPath('data.narrative', 'A corrected account of what happened that night at the house.');

        $report->forceFill(['status' => Report::REVIEWED])->save();

        $this->putJson("/api/v1/reports/{$report->id}", $this->reportBody())->assertForbidden()->assertJsonPath('required_level', 2);
    }

    public function test_a_patrol_officer_cannot_edit_another_officers_report(): void
    {
        $other = User::factory()->clearance(1)->create();
        $theirs = $this->fileAs($other);

        $this->actingAsLevel(1);

        $this->putJson("/api/v1/reports/{$theirs->id}", $this->reportBody())->assertNotFound();
    }

    public function test_a_patrol_officer_cannot_delete_or_review(): void
    {
        $me = $this->actingAsLevel(1);
        $report = $this->fileAs($me);

        $this->deleteJson("/api/v1/reports/{$report->id}")->assertForbidden()->assertJsonPath('required_level', 5);
        $this->postJson("/api/v1/reports/{$report->id}/review")->assertForbidden()->assertJsonPath('required_level', 2);
        $this->assertDatabaseHas('reports', ['id' => $report->id]);
    }

    public function test_a_senior_officer_sees_every_report_and_reviews_them(): void
    {
        $other = User::factory()->clearance(1)->create();
        $report = $this->fileAs($other);

        $reviewer = $this->actingAsLevel(2);

        $this->assertCount(1, $this->getJson('/api/v1/reports')->assertOk()->json('data'));
        $this->assertCount(0, $this->getJson('/api/v1/reports?mine=1')->assertOk()->json('data'));

        $this->postJson("/api/v1/reports/{$report->id}/review")
            ->assertOk()
            ->assertJsonPath('data.status', 'REVIEWED')
            ->assertJsonPath('data.reviewed_by', $reviewer->name);

        $this->assertDatabaseHas('audit_logs', ['action' => 'report_reviewed', 'user_id' => $reviewer->id]);
    }

    public function test_a_senior_officer_can_edit_any_report(): void
    {
        $other = User::factory()->clearance(1)->create();
        $report = $this->fileAs($other);
        $this->actingAsLevel(2);

        $this->putJson("/api/v1/reports/{$report->id}", $this->reportBody(['municipality' => 'Talibon']))->assertOk()->assertJsonPath('data.municipality', 'Talibon');
    }

    public function test_only_the_chief_admin_deletes_a_report(): void
    {
        $other = User::factory()->clearance(1)->create();
        $report = $this->fileAs($other);

        $this->actingAsLevel(4);
        $this->deleteJson("/api/v1/reports/{$report->id}")->assertForbidden();

        $this->actingAsLevel(5);
        $this->deleteJson("/api/v1/reports/{$report->id}")->assertOk();
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_deleted']);
    }

    public function test_report_input_is_checked(): void
    {
        $this->actingAsLevel(1);

        $this->postJson('/api/v1/reports', $this->reportBody(['narrative' => 'Too short']))->assertStatus(422)->assertJsonValidationErrors('narrative');
        $this->postJson('/api/v1/reports', $this->reportBody(['narrative' => '<script>alert(1)</script> and more text to pass length']))->assertStatus(422)->assertJsonValidationErrors('narrative');
        $this->postJson('/api/v1/reports', $this->reportBody(['municipality' => 'Atlantis']))->assertStatus(422)->assertJsonValidationErrors('municipality');
        $this->postJson('/api/v1/reports', $this->reportBody(['occurred_at' => now()->addDay()->toDateTimeString()]))->assertStatus(422)->assertJsonValidationErrors('occurred_at');
        $this->postJson('/api/v1/reports', $this->reportBody(['infraction_type_id' => 99999]))->assertStatus(422)->assertJsonValidationErrors('infraction_type_id');
        $this->postJson('/api/v1/reports', $this->reportBody(['person_name' => '<b>x</b>']))->assertStatus(422)->assertJsonValidationErrors('person_name');
        $this->postJson('/api/v1/reports', ['municipality' => 'Tubigon'])->assertStatus(422);
    }

    public function test_a_report_does_not_need_a_known_person(): void
    {
        $this->actingAsLevel(1);

        $this->postJson('/api/v1/reports', $this->reportBody(['person_name' => null, 'infraction_type_id' => null]))->assertCreated();
    }
}
