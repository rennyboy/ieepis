<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add district_id FK to schools table
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('division')->constrained('districts')->nullOnDelete();
        });

        // Add is_approved / approval_status to users table for fast login check
        Schema::table('users', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('email');
            $table->string('division')->nullable()->after('approval_status');
            $table->unsignedBigInteger('division_id')->nullable()->after('division');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropColumn('district_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'division', 'division_id']);
        });
    }
};
