<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Arr;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class PersonnelTest extends TestCase
{
    private function newPerson(array $overrides = []): array
    {
        return array_merge(
            Arr::except($this->application(['rank' => 'Police Master Sergeant']), ['justification', 'declaration']),
            ['clearance_level' => 2],
            $overrides,
        );
    }

    public function test_only_the_chief_admin_manages_personnel(): void
    {
        $this->actingAsLevel(4);

        $this->getJson('/api/v1/admin/personnel')->assertForbidden();
        $this->postJson('/api/v1/admin/personnel', $this->newPerson())->assertForbidden();
    }

    public function test_the_chief_admin_creates_an_account_with_personal_information(): void
    {
        $this->actingAsLevel(5);

        $response = $this->postJson('/api/v1/admin/personnel', $this->newPerson(['full_name' => 'Juan Dela Cruz']))->assertCreated();

        $user = User::where('badge_id', $response->json('credentials.badge_id'))->firstOrFail();

        $this->assertMatchesRegularExpression('/^BPD-\d{4,6}$/', $user->badge_id);
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('JD', $user->initials);
        $this->assertSame('Police Master Sergeant', $user->rank);
        $this->assertSame(2, $user->clearance_level);
        $this->assertSame('09171234567', $user->phone);
        $this->assertSame('Purok 3, Tubigon', $user->address);
        $this->assertDatabaseHas('audit_logs', ['action' => 'personnel_created']);

        $this->postJson('/api/v1/auth/login', ['badge_id' => $user->badge_id, 'access_key' => $response->json('credentials.password')])->assertOk();
    }

    public function test_a_level_cannot_beat_the_rank(): void
    {
        $this->actingAsLevel(5);

        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['rank' => 'Police Corporal', 'clearance_level' => 2]))
            ->assertStatus(422)->assertJsonValidationErrors('clearance_level');
        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['rank' => 'Detective', 'clearance_level' => 4]))
            ->assertStatus(422)->assertJsonValidationErrors('clearance_level');
    }

    public function test_nobody_is_made_chief_admin_through_the_app(): void
    {
        $this->actingAsLevel(5);

        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['rank' => 'Provincial Director', 'clearance_level' => 5]))->assertStatus(422);
        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['clearance_level' => 5]))->assertStatus(422)->assertJsonValidationErrors('clearance_level');
    }

    public function test_personal_details_are_checked(): void
    {
        $this->actingAsLevel(5);

        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['phone' => 'abc']))->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['station' => 'Atlantis']))->assertStatus(422)->assertJsonValidationErrors('station');
        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['password' => 'weak']))->assertStatus(422)->assertJsonValidationErrors('password');

        User::factory()->create(['email' => 'taken@example.com']);
        $this->postJson('/api/v1/admin/personnel', $this->newPerson(['email' => 'taken@example.com']))->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_editing_a_person_keeps_the_rank_limit(): void
    {
        $this->actingAsLevel(5);
        $user = User::factory()->create(['rank' => 'Detective', 'clearance_level' => 2]);

        $this->putJson("/api/v1/admin/personnel/{$user->id}", ['clearance_level' => 3])->assertOk()->assertJsonPath('data.clearance_level', 3);
        $this->putJson("/api/v1/admin/personnel/{$user->id}", ['clearance_level' => 4])->assertStatus(422)->assertJsonValidationErrors('clearance_level');
        $this->putJson("/api/v1/admin/personnel/{$user->id}", ['rank' => 'Police Corporal'])->assertStatus(422)->assertJsonValidationErrors('clearance_level');
        $this->putJson("/api/v1/admin/personnel/{$user->id}", ['rank' => 'Police Captain', 'clearance_level' => 4])->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'personnel_updated']);
    }

    public function test_chief_admin_accounts_are_protected(): void
    {
        $this->actingAsLevel(5);
        $other = User::factory()->clearance(5)->create();

        $this->putJson("/api/v1/admin/personnel/{$other->id}", ['clearance_level' => 2])->assertStatus(422)->assertJsonValidationErrors('user');
        $this->postJson("/api/v1/admin/personnel/{$other->id}/password", [])->assertStatus(422);
        $this->postJson("/api/v1/admin/personnel/{$other->id}/deactivate")->assertStatus(422);
        $this->assertSame(5, $other->fresh()->clearance_level);
    }

    public function test_issuing_a_password_replaces_the_old_one_and_signs_the_person_out(): void
    {
        $this->actingAsLevel(5);
        $user = User::factory()->clearance(2)->create(['password' => 'old-password-1234']);
        $user->createToken('session');

        $new = $this->postJson("/api/v1/admin/personnel/{$user->id}/password", [])->assertOk()->json('credentials.password');

        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());
        $this->postJson('/api/v1/auth/login', ['badge_id' => $user->badge_id, 'access_key' => 'old-password-1234'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['badge_id' => $user->badge_id, 'access_key' => $new])->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_issued']);
    }

    public function test_deactivating_blocks_login_and_activating_restores_it(): void
    {
        $this->actingAsLevel(5);
        $user = User::factory()->clearance(2)->create(['password' => 'correct-horse-battery']);
        $user->createToken('session');

        $this->postJson("/api/v1/admin/personnel/{$user->id}/deactivate")->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());
        $this->postJson('/api/v1/auth/login', ['badge_id' => $user->badge_id, 'access_key' => 'correct-horse-battery'])->assertUnauthorized();

        $this->postJson("/api/v1/admin/personnel/{$user->id}/activate")->assertOk()->assertJsonPath('data.is_active', true);
        $this->postJson('/api/v1/auth/login', ['badge_id' => $user->badge_id, 'access_key' => 'correct-horse-battery'])->assertOk();
    }

    public function test_the_list_can_be_searched_and_filtered(): void
    {
        $this->actingAsLevel(5);
        User::factory()->clearance(2)->create(['name' => 'Maria Santos']);
        User::factory()->clearance(1)->create(['name' => 'Pedro Reyes']);

        $names = collect($this->getJson('/api/v1/admin/personnel?search=santos')->assertOk()->json('data'))->pluck('name');
        $this->assertSame(['Maria Santos'], $names->all());

        $levels = collect($this->getJson('/api/v1/admin/personnel?level=1')->assertOk()->json('data'))->pluck('clearance_level')->unique();
        $this->assertSame([1], $levels->values()->all());
    }

    public function test_the_summary_counts_people_and_pending_applications(): void
    {
        User::factory()->clearance(2)->create();
        $this->postJson('/api/v1/access-requests', $this->application())->assertCreated();
        $this->actingAsLevel(5);

        $this->getJson('/api/v1/admin/summary')
            ->assertOk()
            ->assertJsonPath('data.pending_requests', 1)
            ->assertJsonPath('data.active_personnel', 2)
            ->assertJsonCount(5, 'data.by_level');
    }

    public function test_archived_records_can_be_listed_and_restored_by_the_chief_admin(): void
    {
        $subject = Subject::factory()->create(['name' => 'Archived Person']);
        $this->actingAsLevel(5);

        $this->deleteJson("/api/v1/subjects/{$subject->id}")->assertOk();
        $this->assertSame(['Archived Person'], collect($this->getJson('/api/v1/admin/archived-subjects')->assertOk()->json('data'))->pluck('name')->all());

        $this->postJson("/api/v1/subjects/{$subject->id}/restore")->assertOk();
        $this->assertCount(0, $this->getJson('/api/v1/admin/archived-subjects')->json('data'));
    }
}
