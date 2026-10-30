<?php

namespace Tests\Feature;

use App\Enums\SubjectStatus;
use App\Models\Subject;
use Tests\TestCase;

class StatusTest extends TestCase
{
    public function test_adding_an_infraction_never_changes_status(): void
    {
        $subject = Subject::factory()->status(SubjectStatus::Paroled)->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId('burglary'),
            'occurred_on' => '2026-01-10',
            'location' => 'Civic District',
        ])->assertCreated();

        $this->assertSame('PAROLED', $subject->fresh()->status->value);
    }

    public function test_deleting_the_last_infraction_does_not_reset_status(): void
    {
        $subject = Subject::factory()->status(SubjectStatus::Cleared)->create();
        $this->actingAsLevel(4);

        $id = $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId('trespassing'),
            'occurred_on' => '2026-01-10',
            'location' => 'Old Quarter',
        ])->json('data.id');

        $this->deleteJson("/api/v1/infractions/{$id}")->assertOk();

        $this->assertSame('CLEARED', $subject->fresh()->status->value);
    }

    public function test_a_manual_status_change_is_recorded_with_its_reason(): void
    {
        $subject = Subject::factory()->create();
        $officer = $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'IN_CUSTODY', 'reason' => 'Arrest report 4120'])
            ->assertOk()
            ->assertJsonPath('data.status', 'IN_CUSTODY');

        $this->assertDatabaseHas('status_changes', [
            'subject_id' => $subject->id,
            'from_status' => 'WANTED',
            'to_status' => 'IN_CUSTODY',
            'reason' => 'Arrest report 4120',
            'changed_by' => $officer->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'status_changed', 'subject_id' => $subject->id]);
    }

    public function test_a_status_change_needs_a_reason(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'CLEARED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_changing_to_the_same_status_is_rejected(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'WANTED', 'reason' => 'No change'])->assertStatus(422);
    }

    public function test_an_apprehended_manhunt_puts_the_subject_in_custody(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['outcome' => 'APPREHENDED']))->assertCreated();

        $this->assertSame('IN_CUSTODY', $subject->fresh()->status->value);
        $this->assertSame(1, $subject->fresh()->times_apprehended);
        $this->assertDatabaseHas('status_changes', ['subject_id' => $subject->id, 'to_status' => 'IN_CUSTODY']);
    }

    public function test_an_evaded_manhunt_makes_a_paroled_subject_wanted(): void
    {
        $subject = Subject::factory()->status(SubjectStatus::Paroled)->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody(['outcome' => 'EVADED']))->assertCreated();

        $this->assertSame('WANTED', $subject->fresh()->status->value);
        $this->assertSame(1, $subject->fresh()->times_evaded);
    }

    public function test_a_manhunt_cannot_be_logged_against_a_cleared_subject(): void
    {
        $subject = Subject::factory()->status(SubjectStatus::Cleared)->create();
        $this->actingAsLevel(2);

        $this->postJson("/api/v1/subjects/{$subject->id}/manhunts", $this->manhuntBody())->assertStatus(422);

        $this->assertSame(0, $subject->manhunts()->count());
    }

    public function test_status_history_lists_changes_newest_first(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'IN_CUSTODY', 'reason' => 'Arrested'])->assertOk();
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'PAROLED', 'reason' => 'Released'])->assertOk();

        $this->getJson("/api/v1/subjects/{$subject->id}/status-history")
            ->assertOk()
            ->assertJsonPath('data.0.to', 'PAROLED')
            ->assertJsonPath('data.1.to', 'IN_CUSTODY');
    }
}
