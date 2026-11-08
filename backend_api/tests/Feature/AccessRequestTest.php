<?php

namespace Tests\Feature;

use App\Models\AccessRequest;
use App\Models\User;
use Tests\TestCase;

class AccessRequestTest extends TestCase
{
    private function submit(array $overrides = [])
    {
        return $this->postJson('/api/v1/access-requests', $this->application($overrides));
    }

    private function idOf(string $reference): int
    {
        return AccessRequest::where('reference', $reference)->value('id');
    }

    // ---- Applying (public)

    public function test_anyone_can_apply_without_signing_in(): void
    {
        $response = $this->submit()->assertCreated();

        $this->assertMatchesRegularExpression('/^ACC-[A-Z0-9]{10}$/', $response->json('data.reference'));
        $response->assertJsonPath('data.status', 'PENDING');
        $this->assertDatabaseHas('access_requests', ['full_name' => 'Juan Dela Cruz', 'status' => 'PENDING']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'access_requested']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_detective_gets_level_3_automatically(): void
    {
        $this->submit(['rank' => 'Detective'])
            ->assertCreated()
            ->assertJsonPath('data.requested_level', 3)
            ->assertJsonPath('data.requested_label', 'Detective')
            ->assertJsonPath('data.auto_level', true);
    }

    public function test_the_level_follows_the_rank(): void
    {
        $expected = [
            'Patrolman / Patrolwoman' => 1,
            'Police Staff Sergeant' => 2,
            'Police Lieutenant' => 3,
            'Police Captain' => 4,
        ];

        foreach ($expected as $rank => $level) {
            $this->submit([
                'rank' => $rank,
                'phone' => '0917'.random_int(1000000, 9999999),
                'full_name' => 'Pedro Santos '.chr(65 + $level),
            ])->assertCreated()->assertJsonPath('data.requested_level', $level)->assertJsonPath('data.auto_level', false);
        }
    }

    public function test_nobody_can_apply_for_chief_admin_through_the_form(): void
    {
        $this->submit(['rank' => 'Provincial Director'])->assertStatus(422)->assertJsonValidationErrors('rank');
    }

    public function test_the_form_lists_ranks_with_their_levels(): void
    {
        $ranks = collect($this->getJson('/api/v1/access-requests/options')->assertOk()->json('data.ranks'));

        $this->assertTrue($ranks->contains(fn ($rank) => $rank['name'] === 'Detective' && $rank['level'] === 3 && $rank['auto'] === true));
        $this->assertFalse($ranks->contains('name', 'Provincial Director'));
    }

    public function test_phone_numbers_are_cleaned_up(): void
    {
        $this->submit(['phone' => '+63 917 123 4567'])->assertCreated();

        $this->assertDatabaseHas('access_requests', ['phone' => '09171234567']);
    }

    public function test_bad_input_is_rejected(): void
    {
        $cases = [
            'phone' => ['phone' => '12345'],
            'date_of_birth' => ['date_of_birth' => now()->subYears(19)->toDateString()],
            'station' => ['station' => 'Atlantis'],
            'address_municipality' => ['address_municipality' => 'Atlantis'],
            'years_of_service' => ['years_of_service' => 30],
            'justification' => ['justification' => '<script>alert(1)</script> I need access'],
            'declaration' => ['declaration' => false],
            'full_name' => ['full_name' => 'Madonna'],
            'email' => ['email' => 'not-an-email'],
        ];

        foreach ($cases as $field => $override) {
            $this->submit($override + ['phone' => '09171230000'])->assertStatus(422)->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('access_requests', 0);
    }

    public function test_the_barangay_must_belong_to_the_municipality_once_barangays_are_loaded(): void
    {
        $tubigon = \App\Models\Municipality::where('name', 'Tubigon')->first();
        \App\Models\Barangay::create(['municipality_id' => $tubigon->id, 'code' => 'T-1', 'name' => 'Poblacion']);

        $this->submit(['address_barangay' => 'Poblacion'])->assertCreated();
        $this->submit(['address_barangay' => 'Nowhere', 'phone' => '09170000001', 'full_name' => 'Maria Clara'])
            ->assertStatus(422)->assertJsonValidationErrors('address_barangay');
    }

    public function test_one_waiting_application_per_person(): void
    {
        $this->submit()->assertCreated();

        $this->submit(['email' => 'other@example.com'])->assertStatus(422)->assertJsonValidationErrors('full_name');
        $this->assertSame(1, AccessRequest::count());
    }

    public function test_a_filled_hidden_field_saves_nothing(): void
    {
        $this->submit(['website' => 'http://spam.example'])->assertCreated();

        $this->assertSame(0, AccessRequest::count());
    }

    public function test_applications_are_rate_limited(): void
    {
        config(['bpd.application.per_hour' => 5]);
        $statuses = [];

        for ($i = 0; $i < 6; $i++) {
            $statuses[] = $this->postJson('/api/v1/access-requests', [])->status();
        }

        $this->assertSame([422, 422, 422, 422, 422, 429], $statuses);
    }

    // ---- Checking the status (public)

    public function test_the_status_page_shows_progress_but_no_personal_details(): void
    {
        $reference = $this->submit()->json('data.reference');

        $this->getJson("/api/v1/access-requests/{$reference}")
            ->assertOk()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.badge_id', null)
            ->assertJsonMissingPath('data.full_name')
            ->assertJsonMissingPath('data.phone');
    }

    public function test_unknown_or_malformed_references_are_not_found(): void
    {
        $this->getJson('/api/v1/access-requests/ACC-AAAAAAAAAA')->assertNotFound();
        $this->getJson('/api/v1/access-requests/nonsense')->assertNotFound();
    }

    // ---- Review (Chief Admin only)

    public function test_only_the_chief_admin_sees_applications(): void
    {
        $this->submit();

        $this->actingAsLevel(4);
        $this->getJson('/api/v1/admin/access-requests')->assertForbidden()->assertJsonPath('required_level', 5);

        $this->actingAsLevel(5);
        $this->getJson('/api/v1/admin/access-requests?status=PENDING')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.max_level', 3);
    }

    public function test_approving_a_detective_creates_an_account_at_level_3(): void
    {
        $reference = $this->submit()->json('data.reference');
        $chief = $this->actingAsLevel(5);

        $response = $this->postJson("/api/v1/admin/access-requests/{$this->idOf($reference)}/approve", [])->assertOk();

        $badge = $response->json('credentials.badge_id');
        $password = $response->json('credentials.password');

        $this->assertMatchesRegularExpression('/^BPD-\d{4,6}$/', $badge);
        $this->assertGreaterThanOrEqual(12, strlen($password));

        $user = User::where('badge_id', $badge)->firstOrFail();

        $this->assertSame(3, $user->clearance_level);
        $this->assertSame('Detective', $user->rank);
        $this->assertSame('Tubigon', $user->station);
        $this->assertSame('Purok 3, Tubigon', $user->address);
        $this->assertSame($chief->id, $user->approved_by);
        $this->assertSame($chief->id, $user->password_issued_by);
        $this->assertDatabaseHas('access_requests', ['reference' => $reference, 'status' => 'APPROVED', 'approved_level' => 3, 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'access_approved']);

        // The password the Chief Admin was shown is the real one, and it was never stored as text.
        $this->assertNotSame($password, $user->password);
        $this->postJson('/api/v1/auth/login', ['badge_id' => $badge, 'access_key' => $password])->assertOk();
    }

    public function test_the_applicant_sees_their_badge_after_approval_but_never_the_password(): void
    {
        $reference = $this->submit()->json('data.reference');
        $this->actingAsLevel(5);

        $badge = $this->postJson("/api/v1/admin/access-requests/{$this->idOf($reference)}/approve", [])->json('credentials.badge_id');

        $status = $this->getJson("/api/v1/access-requests/{$reference}")->assertOk()->assertJsonPath('data.status', 'APPROVED')->assertJsonPath('data.badge_id', $badge);

        $this->assertStringNotContainsString('password', json_encode($status->json()));
    }

    public function test_no_account_exists_before_approval(): void
    {
        $this->submit()->assertCreated();

        $this->postJson('/api/v1/auth/login', ['badge_id' => 'BPD-1234', 'access_key' => 'anything-at-all'])->assertUnauthorized();
        $this->assertSame(0, User::count());
    }

    public function test_other_ranks_need_a_level_chosen_and_it_cannot_beat_the_rank(): void
    {
        $reference = $this->submit(['rank' => 'Police Staff Sergeant'])->json('data.reference');
        $this->actingAsLevel(5);
        $url = "/api/v1/admin/access-requests/{$this->idOf($reference)}/approve";

        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors('level');
        $this->postJson($url, ['level' => 3])->assertStatus(422)->assertJsonValidationErrors('level');
        $this->assertSame('PENDING', AccessRequest::where('reference', $reference)->value('status'));

        $this->postJson($url, ['level' => 2])->assertOk()->assertJsonPath('credentials.level', 2);
    }

    public function test_a_lower_level_than_the_rank_allows_is_fine(): void
    {
        $reference = $this->submit()->json('data.reference');
        $this->actingAsLevel(5);

        $this->postJson("/api/v1/admin/access-requests/{$this->idOf($reference)}/approve", ['level' => 2])->assertOk()->assertJsonPath('credentials.level', 2);
    }

    public function test_the_chief_admin_can_choose_the_password_but_it_must_be_strong(): void
    {
        $reference = $this->submit()->json('data.reference');
        $this->actingAsLevel(5);
        $url = "/api/v1/admin/access-requests/{$this->idOf($reference)}/approve";

        $this->postJson($url, ['password' => 'short1'])->assertStatus(422)->assertJsonValidationErrors('password');
        $this->postJson($url, ['password' => 'onlyletterspassword'])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson($url, ['password' => 'Chief-Issued-2026'])->assertOk()->assertJsonPath('credentials.password', 'Chief-Issued-2026');
    }

    public function test_an_application_is_approved_only_once(): void
    {
        $reference = $this->submit()->json('data.reference');
        $this->actingAsLevel(5);
        $url = "/api/v1/admin/access-requests/{$this->idOf($reference)}/approve";

        $this->postJson($url, [])->assertOk();
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors('request');

        $this->assertSame(1, User::where('rank', 'Detective')->count());
    }

    public function test_an_email_that_is_already_in_use_blocks_approval(): void
    {
        User::factory()->create(['email' => 'juan@example.com']);
        $reference = $this->submit()->json('data.reference');
        $this->actingAsLevel(5);

        $this->postJson("/api/v1/admin/access-requests/{$this->idOf($reference)}/approve", [])->assertStatus(422)->assertJsonValidationErrors('request');
    }

    public function test_rejecting_needs_a_reason_and_the_applicant_can_read_it(): void
    {
        $reference = $this->submit()->json('data.reference');
        $this->actingAsLevel(5);
        $url = "/api/v1/admin/access-requests/{$this->idOf($reference)}/reject";

        $this->postJson($url, ['note' => 'no'])->assertStatus(422)->assertJsonValidationErrors('note');
        $this->postJson($url, ['note' => 'Details do not match our records.'])->assertOk()->assertJsonPath('data.status', 'REJECTED');

        $this->getJson("/api/v1/access-requests/{$reference}")->assertJsonPath('data.status', 'REJECTED')->assertJsonPath('data.note', 'Details do not match our records.');
        $this->assertSame(0, User::count() - 1, 'only the chief admin account exists');
        $this->assertDatabaseHas('audit_logs', ['action' => 'access_rejected']);
    }
}
