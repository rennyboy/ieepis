<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('accountable_officer_id')
                ->nullable()
                ->after('school_id')
                ->constrained('employees')
                ->nullOnDelete();

            $table->index('accountable_officer_id');
        });

        // Backfill the equipment-level accountable officer from each item's
        // existing assignment history: prefer the active (not-yet-returned)
        // assignment, else the most recent. Mirrors the old behaviour where
        // the officer = active assignment's employee_id.
        DB::statement(<<<'SQL'
            UPDATE equipment e
            SET accountable_officer_id = sub.employee_id
            FROM (
                SELECT DISTINCT ON (equipment_id)
                    equipment_id,
                    employee_id
                FROM equipment_assignments
                WHERE deleted_at IS NULL
                  AND employee_id IS NOT NULL
                ORDER BY
                    equipment_id,
                    (returned_at IS NULL) DESC,
                    assigned_at DESC,
                    id DESC
            ) sub
            WHERE e.id = sub.equipment_id
        SQL);
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['accountable_officer_id']);
            $table->dropConstrainedForeignId('accountable_officer_id');
        });
    }
};
