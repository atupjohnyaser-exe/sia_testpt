<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addBadgeId = ! Schema::hasColumn('users', 'badge_id');

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'badge_id')) $table->string('badge_id')->nullable();
            if (! Schema::hasColumn('users', 'initials')) $table->string('initials', 8)->default('AC');
            if (! Schema::hasColumn('users', 'rank')) $table->string('rank')->default('Officer');
            if (! Schema::hasColumn('users', 'clearance_level')) $table->unsignedTinyInteger('clearance_level')->default(3);
        });

        if ($addBadgeId) {
            DB::table('users')->whereNull('badge_id')->orderBy('id')->get(['id', 'name'])->each(function ($user) {
                $initials = collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                DB::table('users')->where('id', $user->id)->update([
                    'badge_id' => 'LEGACY-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                    'initials' => $initials ?: 'OF',
                ]);
            });

            Schema::table('users', fn (Blueprint $table) => $table->unique('badge_id'));
        }
    }

    public function down(): void
    {
        // Keep officer identity data intact when rolling back unrelated newer migrations.
    }
};
