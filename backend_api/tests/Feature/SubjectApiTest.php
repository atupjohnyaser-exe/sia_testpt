<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_can_create_read_update_and_soft_delete_a_subject(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $body = ['name' => 'Mara Voss', 'age' => 34, 'status' => 'WANTED', 'classification' => 'Priority Alpha', 'risk_score' => 86];
        $created = $this->postJson('/api/subjects', $body)->assertCreated()->assertJsonPath('data.name', 'Mara Voss')->json('data');
        $this->getJson('/api/subjects/'.$created['id'])->assertOk()->assertJsonPath('data.case_id', $created['case_id']);
        $this->putJson('/api/subjects/'.$created['id'], ['name' => 'Mara Voss Updated'])->assertOk()->assertJsonPath('data.name', 'Mara Voss Updated');
        $this->deleteJson('/api/subjects/'.$created['id'])->assertOk();
        $this->assertSoftDeleted('subjects', ['id' => $created['id']]);
    }

    public function test_subject_validation_returns_field_errors(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/subjects', ['name' => '', 'age' => 999, 'status' => 'INVALID'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'age', 'status']);
    }
}
