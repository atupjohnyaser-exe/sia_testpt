<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Subject;
use Tests\TestCase;

class AuditTest extends TestCase
{
    public function test_opening_the_same_record_repeatedly_logs_one_view(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk();

        $this->assertSame(1, AuditLog::where('action', 'viewed')->where('subject_id', $subject->id)->count());
    }

    public function test_the_export_is_audited(): void
    {
        $this->actingAsLevel(4);

        $this->get('/api/v1/subjects/export')->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'exported']);
    }

    public function test_the_export_contains_the_records(): void
    {
        Subject::factory()->count(3)->create();
        $this->actingAsLevel(4);

        $content = $this->get('/api/v1/subjects/export')->assertOk()->streamedContent();

        $this->assertCount(3, json_decode($content, true));
    }

    public function test_archiving_and_restoring_are_audited_by_the_observer(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/restore")->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'archived', 'subject_id' => $subject->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'restored', 'subject_id' => $subject->id]);
    }

    public function test_profile_edits_log_the_changed_fields_but_not_their_values(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->putJson("/api/v1/subjects/{$subject->id}", ['name' => 'Brand New Name'])->assertOk();

        $log = AuditLog::where('action', 'updated')->where('subject_id', $subject->id)->firstOrFail();

        $this->assertSame(['name'], $log->meta['changed']);
        $this->assertStringNotContainsString('Brand New Name', json_encode($log->meta));
    }

    public function test_note_text_is_never_copied_into_the_log(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'secret-phrase-123'])->assertCreated();

        $this->assertDatabaseHas('audit_logs', ['action' => 'note_added']);
        $this->assertStringNotContainsString('secret-phrase-123', json_encode(AuditLog::pluck('meta')));
    }

    public function test_the_audit_log_can_be_filtered_by_action(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->getJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'A note'])->assertCreated();

        $actions = collect($this->getJson('/api/v1/audit-logs?action=note_added')->assertOk()->json('data'))->pluck('action')->unique();

        $this->assertSame(['note_added'], $actions->values()->all());
    }

    public function test_audit_rows_cannot_be_changed_or_deleted(): void
    {
        $log = AuditLog::create(['action' => AuditAction::Viewed, 'meta' => ['x' => 1], 'created_at' => now()]);

        $this->assertFalse($log->update(['meta' => ['x' => 2]]));
        $this->assertFalse($log->delete());
        $this->assertSame(['x' => 1], $log->fresh()->meta);
    }
}
