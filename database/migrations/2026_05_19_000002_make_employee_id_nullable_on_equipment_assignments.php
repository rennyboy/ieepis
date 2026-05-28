<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The accountable officer now lives on equipment.accountable_officer_id.
     * equipment_assignments.employee_id is retained (expand/contract — readers
     * such as the PAR/ICS PDF and exports still consume it and it is now
     * auto-derived from the equipment officer), but it is no longer mandatory
     * since an assignment is a custodian record.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE equipment_assignments ALTER COLUMN employee_id DROP NOT NULL');
    }

    public function down(): void
    {
        // Backfill any custodian-only rows before restoring NOT NULL so the
        // rollback cannot fail on nulls introduced after this change.
        DB::statement(<<<'SQL'
            UPDATE equipment_assignments ea
            SET employee_id = e.accountable_officer_id
            FROM equipment e
            WHERE ea.equipment_id = e.id
              AND ea.employee_id IS NULL
              AND e.accountable_officer_id IS NOT NULL
        SQL);

        DB::statement('ALTER TABLE equipment_assignments ALTER COLUMN employee_id SET NOT NULL');
    }
};
