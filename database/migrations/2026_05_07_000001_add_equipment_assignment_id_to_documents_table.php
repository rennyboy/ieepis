<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('equipment_assignment_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('equipment_assignments')
                ->nullOnDelete();

            $table->index(['equipment_assignment_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['equipment_assignment_id', 'document_type']);
            $table->dropConstrainedForeignId('equipment_assignment_id');
        });
    }
};
