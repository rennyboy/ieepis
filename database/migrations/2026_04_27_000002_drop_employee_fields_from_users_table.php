<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity-unification cleanup (hybrid mode). Drops dead `users.name`,
 * `users.division`, `users.division_id`. Keeps `users.school_id` as a
 * denormalized fast-path for SchoolScope; `User::getSchoolIdAttribute()`
 * reads the column directly and falls back to `employee.school_id`.
 *
 * Personal/organizational data still lives on `employees`; the link is
 * `employees.user_id` (unique nullable FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['division_id']);
            $table->dropColumn(['name', 'division', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->after('id');
            $table->string('division')->nullable()->after('approval_status');
            $table->unsignedBigInteger('division_id')->nullable()->after('division');
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
        });
    }
};
