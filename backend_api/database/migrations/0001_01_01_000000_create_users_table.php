<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('badge_id')->unique();
            $table->string('initials', 8)->default('OF');
            $table->string('rank')->default('Officer');
            $table->unsignedTinyInteger('clearance_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
