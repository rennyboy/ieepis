<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ppe_physical_count_items', function (Blueprint $table) {
            $table->unique(['physical_count_id', 'property_number'], 'uq_physical_count_property');
        });
    }

    public function down(): void
    {
        Schema::table('ppe_physical_count_items', function (Blueprint $table) {
            $table->dropUnique('uq_physical_count_property');
        });
    }
};
