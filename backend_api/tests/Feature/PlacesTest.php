<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    private function fakePsgc(): void
    {
        config(['bpd.psgc.retry_ms' => 0]);

        Http::fake(function ($request) {
            $url = $request->url();

            return match (true) {
                str_ends_with($url, '/provinces/Bohol/municipalities') => Http::response([
                    ['code' => '0701201000', 'name' => 'Alburquerque', 'type' => 'Mun'],
                    ['code' => '0701202000', 'name' => 'Alicia', 'type' => 'Mun'],
                ]),
                str_ends_with($url, '/provinces/Bohol/cities') => Http::response([
                    ['code' => '0701222000', 'name' => 'City of Tagbilaran', 'type' => 'City'],
                ]),
                str_ends_with($url, '/cities-municipalities/0701201000/barangays') => Http::response([
                    ['code' => '0701201001', 'name' => 'Bahi'],
                    ['code' => '0701201002', 'name' => 'Basacdacu'],
                ]),
                str_ends_with($url, '/cities-municipalities/0701202000/barangays') => Http::response([
                    ['code' => '0701202001', 'name' => 'Cagongcagong'],
                ]),
                default => Http::response([]),
            };
        });
    }

    public function test_a_fresh_install_has_the_towns_of_bohol(): void
    {
        $names = collect($this->getJson('/api/v1/places/municipalities')->assertOk()->json('data'))->pluck('name');

        $this->assertCount(48, $names);
        $this->assertTrue($names->contains('Tubigon'));
        $this->assertTrue($names->contains('City of Tagbilaran'));
        $this->assertTrue($names->contains('President Carlos P. Garcia'));
    }

    public function test_barangays_come_back_sorted_for_one_municipality(): void
    {
        $town = Municipality::where('name', 'Tubigon')->first();
        Barangay::create(['municipality_id' => $town->id, 'code' => 'T-2', 'name' => 'Pooc Occidental']);
        Barangay::create(['municipality_id' => $town->id, 'code' => 'T-1', 'name' => 'Banlasan']);

        $this->getJson("/api/v1/places/municipalities/{$town->id}/barangays")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Banlasan')
            ->assertJsonPath('data.1.name', 'Pooc Occidental');
    }

    public function test_sync_loads_real_municipalities_and_barangays_from_psgc(): void
    {
        $this->fakePsgc();

        $this->artisan('bpd:sync-places')->assertExitCode(0);

        $this->assertSame(3, Municipality::count());
        $this->assertSame(0, Municipality::where('code', 'like', 'FB-%')->count());
        $this->assertSame('0701201000', Municipality::where('name', 'Alburquerque')->value('code'));
        $this->assertSame('City', Municipality::where('name', 'City of Tagbilaran')->value('type'));
        $this->assertSame(3, Barangay::count());
        $this->assertSame(['Bahi', 'Basacdacu'], Barangay::where('municipality_id', Municipality::where('name', 'Alburquerque')->value('id'))->orderBy('name')->pluck('name')->all());
    }

    public function test_sync_can_run_again_without_duplicating_anything(): void
    {
        $this->fakePsgc();

        $this->artisan('bpd:sync-places')->assertExitCode(0);
        $this->artisan('bpd:sync-places')->assertExitCode(0);

        $this->assertSame(3, Municipality::count());
        $this->assertSame(3, Barangay::count());
    }

    public function test_sync_can_skip_barangays(): void
    {
        $this->fakePsgc();

        $this->artisan('bpd:sync-places --skip-barangays')->assertExitCode(0);

        $this->assertSame(3, Municipality::count());
        $this->assertSame(0, Barangay::count());
    }

    public function test_a_failed_sync_leaves_the_built_in_list_alone(): void
    {
        config(['bpd.psgc.retry_ms' => 0]);
        Http::fake(['*' => Http::response(['message' => 'down'], 500)]);

        $this->artisan('bpd:sync-places')->assertExitCode(1);

        $this->assertSame(48, Municipality::count());
    }

    public function test_barangays_are_checked_against_the_real_list_once_loaded(): void
    {
        $this->fakePsgc();
        $this->artisan('bpd:sync-places')->assertExitCode(0);
        $this->actingAsLevel(1);

        $this->postJson('/api/v1/reports', $this->reportBody(['municipality' => 'Alburquerque', 'barangay' => 'Bahi']))->assertCreated();
        $this->postJson('/api/v1/reports', $this->reportBody(['municipality' => 'Alburquerque', 'barangay' => 'Cagongcagong']))
            ->assertStatus(422)->assertJsonValidationErrors('barangay');
        $this->postJson('/api/v1/reports', $this->reportBody(['municipality' => 'City of Tagbilaran', 'barangay' => 'Anything goes here']))->assertCreated();
    }

    public function test_the_place_lists_need_no_login(): void
    {
        $this->getJson('/api/v1/places/municipalities')->assertOk();
    }
}
