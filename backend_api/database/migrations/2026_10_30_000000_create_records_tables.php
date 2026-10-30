<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sanctum may add this table itself. Only create it if nobody did.
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        Schema::create('infraction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('severity', 10);
            $table->unsignedInteger('fine_amount')->default(0);
            $table->unsignedInteger('bounty_value')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cost_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('label');
            $table->unsignedInteger('unit_cost')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('case_id', 32)->unique();
            $table->string('name', 160)->index();
            $table->string('alias', 120)->nullable()->index();
            $table->unsignedTinyInteger('age')->nullable();

            // Status is explicit. Only StatusService changes it, and every change is kept in status_changes.
            $table->string('status', 20)->default('WANTED')->index();

            // Everything below is calculated by SubjectStatsService, never typed in by hand.
            $table->unsignedTinyInteger('threat_level')->default(1)->index();
            $table->unsignedBigInteger('bounty')->default(0)->index();
            $table->unsignedBigInteger('cost_to_state')->default(0);
            $table->unsignedBigInteger('fines_due')->default(0);
            $table->unsignedBigInteger('fines_paid')->default(0);
            $table->unsignedInteger('total_infractions')->default(0);
            $table->unsignedInteger('unserved_infractions')->default(0);
            $table->unsignedInteger('times_evaded')->default(0);
            $table->unsignedInteger('times_apprehended')->default(0);

            // Supervisor override of the calculated threat level.
            $table->unsignedTinyInteger('threat_override')->nullable();
            $table->string('threat_override_reason', 500)->nullable();
            $table->foreignId('threat_override_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('last_known_location', 160)->nullable();
            $table->string('height', 40)->nullable();
            $table->string('eye_color', 60)->nullable();
            $table->string('hair_color', 60)->nullable();
            $table->text('distinguishing_features')->nullable();
            $table->string('photo_url', 2048)->nullable();

            $table->text('officer_advisory')->nullable();
            $table->foreignId('advisory_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('advisory_reviewed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('manhunts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->unsignedInteger('duration_seconds');
            $table->string('outcome', 20);
            $table->string('district', 120);

            $table->unsignedInteger('officers_involved')->default(0);
            $table->unsignedInteger('officers_injured')->default(0);
            $table->unsignedInteger('units_stood_down')->default(0);
            $table->unsignedInteger('checkpoints')->default(0);
            $table->unsignedInteger('k9_units')->default(0);
            $table->unsignedInteger('helicopters')->default(0);
            $table->unsignedInteger('property_damage_incidents')->default(0);
            $table->unsignedInteger('insurance_claims')->default(0);

            // Calculated when the manhunt is logged and stored, so later rate changes do not rewrite history.
            $table->unsignedBigInteger('cost_to_state')->default(0);
            $table->unsignedBigInteger('bounty_value')->default(0);
            $table->json('cost_breakdown')->nullable();

            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'bounty_value']);
            $table->index('duration_seconds');
        });

        Schema::create('infractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('infraction_type_id')->constrained('infraction_types')->restrictOnDelete();
            $table->foreignId('manhunt_id')->nullable()->constrained('manhunts')->nullOnDelete();
            $table->date('occurred_on');
            $table->string('location', 180);
            $table->string('disposition', 180)->nullable();

            // Copied from the type when the infraction is recorded.
            $table->unsignedInteger('fine_amount')->default(0);
            $table->unsignedInteger('bounty_value')->default(0);

            $table->timestamp('served_at')->nullable();
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fine_paid_at')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_id', 'served_at']);
            $table->index('occurred_on');
        });

        Schema::create('sightings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('district', 120);
            $table->string('address', 180)->nullable();
            $table->dateTime('seen_at');
            $table->string('source', 40)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_id', 'district']);
        });

        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->unsignedTinyInteger('access_level')->default(3);
            $table->timestamps();
        });

        Schema::create('status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('reason', 500);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 40)->unique();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40)->index();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'bulletins', 'status_changes', 'case_notes', 'sightings', 'infractions', 'manhunts', 'subjects', 'cost_rates', 'infraction_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
