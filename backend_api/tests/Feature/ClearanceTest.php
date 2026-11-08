<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class ClearanceTest extends TestCase
{
    // ---- Level 1: Patrol Officer. Files reports. Sees no people.

    public function test_a_patrol_officer_cannot_open_any_record_of_a_person(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(1);

        foreach (['/subjects', "/subjects/{$subject->id}", "/subjects/{$subject->id}/rap-sheet", '/infractions', '/manhunts', '/rankings', '/dashboard/priority', "/subjects/{$subject->id}/analytics"] as $path) {
            $this->getJson('/api/v1'.$path)->assertForbidden()->assertJsonPath('code', 'clearance_required')->assertJsonPath('required_level', 2);
        }
    }

    public function test_a_patrol_officer_can_see_the_dashboard_numbers_but_not_who_is_behind_them(): void
    {
        $this->actingAsLevel(1);

        $this->getJson('/api/v1/dashboard/stats')->assertOk();
        $this->getJson('/api/v1/bulletins/current')->assertOk();
        $this->getJson('/api/v1/infraction-types')->assertOk();
    }

    public function test_a_patrol_officer_cannot_change_or_delete_anything(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(1);

        $this->postJson('/api/v1/subjects', ['name' => 'Test Person'])->assertForbidden();
        $this->putJson("/api/v1/subjects/{$subject->id}", ['name' => 'Other Name'])->assertForbidden();
        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertForbidden();
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'A note'])->assertForbidden();
    }

    public function test_a_refused_request_tells_you_what_is_missing_and_is_logged(): void
    {
        $this->actingAsLevel(1);

        $this->getJson('/api/v1/subjects')
            ->assertForbidden()
            ->assertJsonPath('required_label', 'Senior Officer')
            ->assertJsonPath('your_level', 1)
            ->assertJsonPath('your_label', 'Patrol Officer');

        $this->assertDatabaseHas('audit_logs', ['action' => 'access_denied']);
    }

    // ---- Level 2: Senior Officer. Reads and edits most of it.

    public function test_a_senior_officer_reads_the_database_and_adds_field_records(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->getJson('/api/v1/subjects')->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/sightings", [
            'district' => 'Loboc',
            'seen_at' => now()->subHour()->toDateTimeString(),
        ])->assertCreated();
    }

    public function test_a_senior_officer_needs_a_higher_level_to_create_or_change_records(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson('/api/v1/subjects', ['name' => 'Test Person'])->assertForbidden()->assertJsonPath('required_level', 3);
        $this->putJson("/api/v1/subjects/{$subject->id}", ['name' => 'Other Name'])->assertForbidden();
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'PAROLED', 'reason' => 'Released'])->assertForbidden();
        $this->postJson("/api/v1/subjects/{$subject->id}/case-file")->assertForbidden();
    }

    // ---- Level 3: Detective. Like the old level 3.

    public function test_a_detective_creates_records_but_cannot_archive_export_or_read_the_audit_log(): void
    {
        $this->actingAsLevel(3);

        $created = $this->postJson('/api/v1/subjects', ['name' => 'Test Person', 'age' => 30])->assertCreated();
        $id = $created->json('data.id');

        $this->assertSame('WANTED', $created->json('data.status'));
        $this->assertMatchesRegularExpression('/^BPD-\d{4}-[A-Z]{2}$/', $created->json('data.case_id'));

        $this->deleteJson("/api/v1/subjects/{$id}")->assertForbidden()->assertJsonPath('required_level', 4);
        $this->getJson('/api/v1/subjects/export')->assertForbidden();
        $this->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    // ---- Level 4: Supervisor.

    public function test_a_supervisor_archives_exports_and_reads_the_audit_log(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->getJson('/api/v1/subjects/export')->assertOk();
        $this->getJson('/api/v1/audit-logs')->assertOk();
        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}")->assertNotFound();
    }

    public function test_a_supervisor_cannot_touch_crime_history_or_restore_records(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $infractionId = $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId('fraud'),
            'occurred_on' => '2026-01-01',
            'location' => 'Tubigon',
        ])->json('data.id');

        $this->deleteJson("/api/v1/infractions/{$infractionId}")->assertForbidden()->assertJsonPath('required_level', 5);

        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/restore")->assertForbidden()->assertJsonPath('required_level', 5);
        $this->getJson('/api/v1/admin/summary')->assertForbidden()->assertJsonPath('required_level', 5);
    }

    // ---- Level 5: Chief Admin.

    public function test_the_chief_admin_corrects_history_and_restores_records(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(5);

        $infractionId = $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId('fraud'),
            'occurred_on' => '2026-01-01',
            'location' => 'Tubigon',
        ])->json('data.id');

        $this->deleteJson("/api/v1/infractions/{$infractionId}")->assertOk();
        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/restore")->assertOk();
        $this->getJson('/api/v1/admin/summary')->assertOk();
    }

    public function test_clearing_a_record_needs_the_chief_admin(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAsLevel(4);
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'CLEARED', 'reason' => 'Charges dropped'])
            ->assertForbidden()
            ->assertJsonPath('required_level', 5);
        $this->assertSame('WANTED', $subject->fresh()->status->value);

        $this->actingAsLevel(5);
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'CLEARED', 'reason' => 'Charges dropped'])->assertOk();
        $this->assertSame('CLEARED', $subject->fresh()->status->value);
    }

    public function test_reopening_a_cleared_record_also_needs_the_chief_admin(): void
    {
        $subject = Subject::factory()->status(\App\Enums\SubjectStatus::Cleared)->create();

        $this->actingAsLevel(4);
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'WANTED', 'reason' => 'New evidence'])->assertForbidden();

        $this->actingAsLevel(5);
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'WANTED', 'reason' => 'New evidence'])->assertOk();
    }

    public function test_other_status_changes_stay_with_detectives(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'IN_CUSTODY', 'reason' => 'Arrest report 12'])->assertOk();
    }

    // ---- Shared rules

    public function test_a_deactivated_officer_is_locked_out_even_with_a_valid_token(): void
    {
        $user = $this->actingAsLevel(5);
        $user->forceFill(['is_active' => false])->save();

        $this->getJson('/api/v1/subjects')->assertForbidden();
    }

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->getJson('/api/v1/subjects')->assertUnauthorized();
        $this->getJson('/api/v1/admin/summary')->assertUnauthorized();
        $this->getJson('/api/v1/reports')->assertUnauthorized();
    }

    public function test_status_and_stats_cannot_be_set_through_the_update_endpoint(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->putJson("/api/v1/subjects/{$subject->id}", [
            'name' => 'Renamed',
            'status' => 'CLEARED',
            'bounty' => 999,
            'threat_level' => 6,
        ])->assertOk();

        $subject->refresh();

        $this->assertSame('Renamed', $subject->name);
        $this->assertSame('WANTED', $subject->status->value);
        $this->assertSame(0, $subject->bounty);
        $this->assertSame(1, $subject->threat_level);
    }

    public function test_photo_url_must_be_http_or_https(): void
    {
        $this->actingAsLevel(3);

        $this->postJson('/api/v1/subjects', ['name' => 'Test Person', 'photo_url' => 'javascript:alert(1)'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo_url');
    }

    public function test_restricted_notes_are_hidden_from_lower_clearance(): void
    {
        $subject = Subject::factory()->create();

        $this->actingAsLevel(3);
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'Sensitive detail', 'access_level' => 3])->assertCreated();
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'Public detail', 'access_level' => 2])->assertCreated();

        $this->actingAsLevel(2);
        $bodies = collect($this->getJson("/api/v1/subjects/{$subject->id}/notes")->assertOk()->json('data'))->pluck('body');

        $this->assertTrue($bodies->contains('RESTRICTED'));
        $this->assertTrue($bodies->contains('Public detail'));
        $this->assertFalse($bodies->contains('Sensitive detail'));
    }

    public function test_an_officer_cannot_lock_a_note_above_their_own_clearance(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'Too high', 'access_level' => 4])->assertStatus(422);
    }
}
