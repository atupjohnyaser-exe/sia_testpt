<?php

namespace Tests\Feature;

use App\Models\Subject;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    public function test_names_may_use_letters_from_any_language_and_normal_punctuation(): void
    {
        $this->actingAsLevel(3);

        foreach (['José O’Brien-Smith', 'Mara Voss', 'Dr. Åsa Lindqvist', '山田 太郎'] as $name) {
            $this->postJson('/api/v1/subjects', ['name' => $name])->assertCreated();
        }
    }

    public function test_names_reject_digits_html_and_symbols(): void
    {
        $this->actingAsLevel(3);

        foreach (['R2D2', '<script>alert(1)</script>', 'Mara; DROP TABLE subjects', 'A', str_repeat('a', 121)] as $name) {
            $this->postJson('/api/v1/subjects', ['name' => $name])->assertStatus(422)->assertJsonValidationErrors('name');
        }
    }

    public function test_hidden_direction_and_control_characters_are_rejected(): void
    {
        $this->actingAsLevel(3);

        $this->postJson('/api/v1/subjects', ['name' => "Mara\u{202E}ssoV"])->assertStatus(422);
        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'last_known_location' => "Harbor\x07"])->assertStatus(422);
        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'last_known_location' => "Zero\u{200B}width"])->assertStatus(422);
    }

    public function test_free_text_rejects_html_but_allows_normal_symbols(): void
    {
        $this->actingAsLevel(3);

        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'officer_advisory' => '<img src=x onerror=alert(1)>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('officer_advisory');

        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'officer_advisory' => 'Seen with 5 < 6 associates, a 50% chance & a red van.'])
            ->assertCreated();
    }

    public function test_age_must_be_a_whole_number_between_1_and_120(): void
    {
        $this->actingAsLevel(3);

        foreach ([0, 121, -5, 'abc', 30.5] as $age) {
            $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'age' => $age])->assertStatus(422)->assertJsonValidationErrors('age');
        }

        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'age' => 120])->assertCreated();
    }

    public function test_height_and_colours_use_simple_formats(): void
    {
        $this->actingAsLevel(3);

        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'height' => '5\'8"', 'eye_color' => 'Light brown', 'hair_color' => 'Blue-green'])->assertCreated();
        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'height' => '<b>tall</b>'])->assertStatus(422)->assertJsonValidationErrors('height');
        $this->postJson('/api/v1/subjects', ['name' => 'Mara Voss', 'eye_color' => 'Blue123'])->assertStatus(422)->assertJsonValidationErrors('eye_color');
    }

    public function test_the_update_endpoint_applies_the_same_rules(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->putJson("/api/v1/subjects/{$subject->id}", ['name' => '<b>x</b>'])->assertStatus(422);
        $this->putJson("/api/v1/subjects/{$subject->id}", ['age' => 500])->assertStatus(422);
        $this->putJson("/api/v1/subjects/{$subject->id}", ['alias' => 'Mr. "K"'])->assertOk();
    }

    public function test_login_input_is_limited(): void
    {
        $this->postJson('/api/v1/auth/login', ['badge_id' => 'bad badge!', 'access_key' => 'x'])->assertStatus(422)->assertJsonValidationErrors('badge_id');
        $this->postJson('/api/v1/auth/login', ['badge_id' => 'RP-1047', 'access_key' => str_repeat('a', 129)])->assertStatus(422)->assertJsonValidationErrors('access_key');
        $this->postJson('/api/v1/auth/login', ['badge_id' => str_repeat('A', 21), 'access_key' => 'x'])->assertStatus(422);
    }

    public function test_infraction_text_and_dates_are_limited(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $base = ['infraction_type_id' => $this->typeId('fraud'), 'occurred_on' => '2026-01-01', 'location' => 'Civic District'];
        $url = "/api/v1/subjects/{$subject->id}/infractions";

        $this->postJson($url, array_merge($base, ['occurred_on' => '1960-01-01']))->assertStatus(422)->assertJsonValidationErrors('occurred_on');
        $this->postJson($url, array_merge($base, ['location' => str_repeat('x', 121)]))->assertStatus(422)->assertJsonValidationErrors('location');
        $this->postJson($url, array_merge($base, ['disposition' => '<script>']))->assertStatus(422)->assertJsonValidationErrors('disposition');
        $this->postJson($url, array_merge($base, ['infraction_type_id' => 99999]))->assertStatus(422)->assertJsonValidationErrors('infraction_type_id');
    }

    public function test_manhunt_numbers_have_limits(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);
        $url = "/api/v1/subjects/{$subject->id}/manhunts";

        $this->postJson($url, $this->manhuntBody(['helicopters' => 51]))->assertStatus(422)->assertJsonValidationErrors('helicopters');
        $this->postJson($url, $this->manhuntBody(['officers_involved' => -1]))->assertStatus(422)->assertJsonValidationErrors('officers_involved');
        $this->postJson($url, $this->manhuntBody(['k9_units' => 'many']))->assertStatus(422)->assertJsonValidationErrors('k9_units');
        $this->postJson($url, $this->manhuntBody(['duration_seconds' => 172801]))->assertStatus(422)->assertJsonValidationErrors('duration_seconds');
        $this->postJson($url, $this->manhuntBody(['duration_seconds' => 0]))->assertStatus(422)->assertJsonValidationErrors('duration_seconds');
        $this->postJson($url, $this->manhuntBody(['started_at' => now()->addDay()->toDateTimeString()]))->assertStatus(422)->assertJsonValidationErrors('started_at');
        $this->postJson($url, $this->manhuntBody(['outcome' => 'ESCAPED']))->assertStatus(422)->assertJsonValidationErrors('outcome');
        $this->postJson($url, $this->manhuntBody(['infractions' => array_fill(0, 21, ['infraction_type_id' => $this->typeId('fraud')])]))->assertStatus(422)->assertJsonValidationErrors('infractions');
        $this->postJson($url, $this->manhuntBody(['helicopters' => 50, 'k9_units' => 200]))->assertCreated();
    }

    public function test_sightings_and_notes_are_limited(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $sighting = ['district' => 'Old Quarter', 'seen_at' => now()->subHour()->toDateTimeString()];

        $this->postJson("/api/v1/subjects/{$subject->id}/sightings", array_merge($sighting, ['source' => 'rumour']))->assertStatus(422)->assertJsonValidationErrors('source');
        $this->postJson("/api/v1/subjects/{$subject->id}/sightings", array_merge($sighting, ['address' => str_repeat('a', 151)]))->assertStatus(422)->assertJsonValidationErrors('address');
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => str_repeat('a', 5001)])->assertStatus(422)->assertJsonValidationErrors('body');
        $this->postJson("/api/v1/subjects/{$subject->id}/notes", ['body' => 'ab'])->assertStatus(422)->assertJsonValidationErrors('body');
    }

    public function test_status_and_threat_reasons_are_limited(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'PAROLED', 'reason' => str_repeat('a', 301)])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->postJson("/api/v1/subjects/{$subject->id}/status", ['status' => 'PAROLED', 'reason' => '<b>x</b> reason'])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->putJson("/api/v1/subjects/{$subject->id}/threat-override", ['level' => 7, 'reason' => 'Too high'])->assertStatus(422)->assertJsonValidationErrors('level');
    }

    public function test_cost_rates_are_limited(): void
    {
        $this->actingAsLevel(4);

        $this->putJson('/api/v1/cost-rates', ['rates' => [['code' => 'helicopters', 'unit_cost' => 1000001]]])->assertStatus(422);
        $this->putJson('/api/v1/cost-rates', ['rates' => [['code' => 'helicopters', 'unit_cost' => -1]]])->assertStatus(422);
        $this->putJson('/api/v1/cost-rates', ['rates' => [['code' => 'nonsense', 'unit_cost' => 5]]])->assertStatus(422);
        $this->putJson('/api/v1/cost-rates', ['rates' => [['code' => 'helicopters', 'unit_cost' => 5], ['code' => 'helicopters', 'unit_cost' => 6]]])->assertStatus(422);
    }

    public function test_list_queries_are_validated(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->getJson('/api/v1/subjects?page=0')->assertStatus(422);
        $this->getJson('/api/v1/subjects?per_page=101')->assertStatus(422);
        $this->getJson('/api/v1/subjects?search='.str_repeat('a', 101))->assertStatus(422);
        $this->getJson('/api/v1/subjects?sort=password')->assertStatus(422);
        $this->getJson('/api/v1/subjects?status=ESCAPED')->assertStatus(422);
        $this->getJson("/api/v1/subjects/{$subject->id}/infractions?served=maybe")->assertStatus(422);
        $this->getJson("/api/v1/subjects/{$subject->id}/infractions?served=false")->assertOk();
        $this->getJson("/api/v1/subjects/{$subject->id}/manhunts?sort=nope")->assertStatus(422);
        $this->getJson("/api/v1/subjects/{$subject->id}/manhunts?top=26")->assertStatus(422);
        $this->getJson('/api/v1/infractions?type=Drop%20Table')->assertStatus(422);
        $this->getJson('/api/v1/audit-logs?from=2026-05-01&to=2026-04-01')->assertStatus(422);
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->actingAsLevel(1);

        $this->getJson('/api/v1/subjects')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
    }
}
