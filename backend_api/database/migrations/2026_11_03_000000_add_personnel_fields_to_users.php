<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Personal information the Chief Admin records for every person with access.
            $table->string('email', 160)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('station', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->unsignedTinyInteger('years_of_service')->nullable();

            // How the account came to exist.
            $table->unsignedBigInteger('access_request_id')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Only the Chief Admin issues passwords.
            $table->foreignId('password_issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('password_issued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['password_issued_by']);
            $table->dropColumn(['email', 'phone', 'date_of_birth', 'station', 'address', 'years_of_service', 'access_request_id', 'approved_by', 'approved_at', 'password_issued_by', 'password_issued_at']);
        });
    }
};
