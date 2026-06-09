<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ppe_physical_counts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('ppe_physical_counts', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
        });
    }
};
