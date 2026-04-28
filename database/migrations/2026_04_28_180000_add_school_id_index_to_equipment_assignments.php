<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `school_id` index on equipment_assignments. SchoolScope filters every
 * assignment query by school_id; without an explicit index, Postgres falls
 * back to a sequential scan as the table grows. Pairs with the existing
 * `(equipment_id, returned_at)` and `(employee_id, returned_at)` composite
 * indexes from the original create migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_assignments', function (Blueprint $table): void {
            $table->index('school_id', 'equipment_assignments_school_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_assignments', function (Blueprint $table): void {
            $table->dropIndex('equipment_assignments_school_id_index');
        });
    }
};
