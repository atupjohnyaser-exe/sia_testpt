<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('case_id')->unique();
            $table->string('name')->index();
            $table->string('alias')->nullable()->index();
            $table->unsignedTinyInteger('age')->nullable();
            $table->enum('status', ['WANTED', 'IN_CUSTODY', 'PAROLED', 'CLEARED'])->default('WANTED')->index();
            $table->string('classification')->default('Under review');
            $table->string('last_known_location')->nullable();
            $table->string('height')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('hair_color')->nullable();
            $table->text('distinguishing_features')->nullable();
            $table->string('photo_url')->nullable();
            $table->unsignedTinyInteger('risk_score')->default(15)->index();
            $table->boolean('risk_override')->default(false);
            $table->text('officer_advisory')->nullable();
            $table->foreignId('advisory_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('advisory_reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('offenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('category')->index();
            $table->string('charge');
            $table->date('occurred_on')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('disposition')->nullable();
            $table->enum('status_effect', ['WANTED', 'IN_CUSTODY', 'PAROLED', 'CLEARED'])->default('WANTED');
            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH'])->default('LOW');
            $table->timestamps();
        });

        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->unsignedTinyInteger('access_level')->default(3);
            $table->timestamps();
        });

        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->string('ref')->unique();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

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

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('bulletins');
        Schema::dropIfExists('case_notes');
        Schema::dropIfExists('offenses');
        Schema::dropIfExists('subjects');
    }
};
