<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unifies user/employee identity. Personal/organizational fields live on
 * `employees`; `users` is now an auth-only shell linked to an employee via
 * `employees.user_id`. See AssignmentService-style contract: `App\Models\User`
 * delegates `name`/`school_id`/`division_id` reads to its employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['school_id']);
            $table->dropColumn(['name', 'school_id', 'division', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->after('id');
            $table->foreignId('school_id')->nullable()->after('email')->constrained()->cascadeOnDelete();
            $table->string('division')->nullable()->after('approval_status');
            $table->unsignedBigInteger('division_id')->nullable()->after('division');
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
        });
    }
};
