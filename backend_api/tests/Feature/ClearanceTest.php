<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class ClearanceTest extends TestCase
{
    public function test_a_clerk_can_read_but_not_write(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(1);

        $this->getJson('/api/v1/subjects')->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->postJson('/api/v1/subjects', ['name' => 'Test Person'])->assertForbidden();
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'A note'])->assertForbidden();
    }

    public function test_an_officer_can_add_field_records_but_not_create_subjects(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/sightings", [
            'district' => 'Old Quarter',
            'seen_at' => now()->subHour()->toDateTimeString(),
        ])->assertCreated();

        $this->postJson('/api/v1/subjects', ['name' => 'Test Person'])->assertForbidden();
    }

    public function test_a_detective_can_create_but_not_archive_export_or_read_the_audit_log(): void
    {
        $this->actingAsLevel(3);

        $created = $this->postJson('/api/v1/subjects', ['name' => 'Test Person', 'age' => 30])->assertCreated();
        $id = $created->json('data.id');

        $this->assertSame('WANTED', $created->json('data.status'));
        $this->assertMatchesRegularExpression('/^RP-\d{4}-[A-Z]{2}$/', $created->json('data.case_id'));

        $this->deleteJson("/api/v1/subjects/{$id}")->assertForbidden();
        $this->getJson('/api/v1/subjects/export')->assertForbidden();
        $this->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    public function test_a_supervisor_can_archive_restore_export_and_read_the_audit_log(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}")->assertNotFound();
        $this->postJson("/api/v1/subjects/{$subject->id}/restore")->assertOk();
        $this->getJson('/api/v1/subjects/export')->assertOk();
        $this->getJson('/api/v1/audit-logs')->assertOk();
    }

    public function test_a_deactivated_officer_is_locked_out_even_with_a_valid_token(): void
    {
        $user = $this->actingAsLevel(4);
        $user->forceFill(['is_active' => false])->save();

        $this->getJson('/api/v1/subjects')->assertForbidden();
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
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'Public detail', 'access_level' => 1])->assertCreated();

        $this->actingAsLevel(1);
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
