<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Real places from PSGC. Filled by: php artisan bpd:sync-places
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100)->unique();
            $table->string('type', 30)->default('Municipality');
            $table->timestamps();
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->timestamps();

            $table->index(['municipality_id', 'name']);
        });

        // Someone asking for access. Nothing here can log in. Only the Chief Admin turns it into an account.
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('full_name', 120);
            $table->string('rank', 60);
            $table->unsignedTinyInteger('requested_level');
            $table->boolean('auto_level')->default(false);
            $table->date('date_of_birth');
            $table->string('phone', 20);
            $table->string('email', 160)->nullable();
            $table->string('station', 100);
            $table->string('address_municipality', 100);
            $table->string('address_barangay', 100)->nullable();
            $table->string('address_street', 150)->nullable();
            $table->unsignedTinyInteger('years_of_service');
            $table->text('justification');
            $table->string('status', 12)->default('PENDING')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 300)->nullable();
            $table->unsignedTinyInteger('approved_level')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // Field reports. Patrol officers file these without seeing any records of people.
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('filed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('infraction_type_id')->nullable()->constrained('infraction_types')->nullOnDelete();
            $table->dateTime('occurred_at');
            $table->string('municipality', 100);
            $table->string('barangay', 100)->nullable();
            $table->string('street', 150)->nullable();
            $table->string('person_name', 120)->nullable();
            $table->string('person_description', 500)->nullable();
            $table->text('narrative');
            $table->string('status', 12)->default('FILED')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['filed_by', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['reports', 'access_requests', 'barangays', 'municipalities'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
