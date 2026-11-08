<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The department is now Bohol Police Department.
 *  - Badge IDs and case IDs that start with RP- now start with BPD-.
 *  - The made-up districts in the old demo data become real Bohol municipalities.
 * Nothing else is touched, and running it twice does no harm.
 */
return new class extends Migration
{
    private const PLACES = [
        'South Harbor District' => 'Tubigon',
        'East Rail Yards' => 'Talibon',
        'Civic District' => 'City of Tagbilaran',
        'North Precinct' => 'Ubay',
        'Old Quarter' => 'Loboc',
    ];

    public function up(): void
    {
        $this->renamePrefix('users', 'badge_id');
        $this->renamePrefix('subjects', 'case_id');

        foreach (self::PLACES as $old => $new) {
            DB::table('subjects')->where('last_known_location', $old)->update(['last_known_location' => $new]);
            DB::table('manhunts')->where('district', $old)->update(['district' => $new]);
            DB::table('sightings')->where('district', $old)->update(['district' => $new]);
            DB::table('infractions')->where('location', $old)->update(['location' => $new]);
        }
    }

    public function down(): void
    {
        // The old names are gone for good. There is nothing meaningful to restore.
    }

    private function renamePrefix(string $table, string $column): void
    {
        foreach (DB::table($table)->where($column, 'like', 'RP-%')->get(['id', $column]) as $row) {
            $new = 'BPD-'.substr($row->{$column}, 3);

            if (! DB::table($table)->where($column, $new)->exists()) {
                DB::table($table)->where('id', $row->id)->update([$column => $new]);
            }
        }
    }
};
