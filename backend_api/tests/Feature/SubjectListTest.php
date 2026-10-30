<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class SubjectListTest extends TestCase
{
    public function test_search_matches_name_alias_and_case_id(): void
    {
        Subject::factory()->create(['name' => 'Mara Voss', 'alias' => 'The Switch']);
        Subject::factory()->create(['name' => 'Elias Kane', 'alias' => 'Grey Fox']);
        $this->actingAsLevel(1);

        $this->assertCount(1, $this->getJson('/api/v1/subjects?search=voss')->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/subjects?search=grey')->json('data'));
    }

    public function test_search_treats_percent_signs_literally(): void
    {
        Subject::factory()->create(['name' => 'Mara Voss']);
        $this->actingAsLevel(1);

        $this->assertCount(0, $this->getJson('/api/v1/subjects?search=%25')->json('data'));
    }

    public function test_list_is_paginated_and_sorted_by_bounty_by_default(): void
    {
        Subject::factory()->create(['name' => 'Low', 'bounty' => 10]);
        Subject::factory()->create(['name' => 'High', 'bounty' => 9_000]);
        $this->actingAsLevel(1);

        $response = $this->getJson('/api/v1/subjects?per_page=1')->assertOk();

        $response->assertJsonPath('data.0.name', 'High')->assertJsonPath('meta.total', 2)->assertJsonPath('meta.per_page', 1);
    }

    public function test_dashboard_priority_leaves_out_cleared_subjects(): void
    {
        Subject::factory()->create(['name' => 'Cleared Big', 'bounty' => 9_999_999, 'status' => 'CLEARED']);
        Subject::factory()->create(['name' => 'Wanted Small', 'bounty' => 5]);
        $this->actingAsLevel(1);

        $names = collect($this->getJson('/api/v1/dashboard/priority')->assertOk()->json('data'))->pluck('name');

        $this->assertSame(['Wanted Small'], $names->all());
    }

    public function test_dashboard_stats_reflect_new_records_immediately(): void
    {
        $this->actingAsLevel(3);

        $this->assertSame(0, $this->getJson('/api/v1/dashboard/stats')->json('data.active_records'));

        $this->postJson('/api/v1/subjects', ['name' => 'New Person'])->assertCreated();

        $this->assertSame(1, $this->getJson('/api/v1/dashboard/stats')->json('data.active_records'));
    }

    public function test_deleting_an_infraction_needs_supervisor_clearance(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $id = $this->postJson("/api/v1/subjects/{$subject->id}/infractions", [
            'infraction_type_id' => $this->typeId('fraud'),
            'occurred_on' => '2026-01-01',
            'location' => 'Civic District',
        ])->json('data.id');

        $this->deleteJson("/api/v1/infractions/{$id}")->assertForbidden();
    }

    public function test_responses_are_not_cacheable(): void
    {
        $this->actingAsLevel(1);

        $response = $this->getJson('/api/v1/subjects')->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
