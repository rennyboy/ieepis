<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppe_physical_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_number')->unique();
            $table->date('inventory_date');
            $table->string('inventory_period')->nullable();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('location')->nullable();
            $table->foreignId('conducted_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('inventory_date');
        });

        Schema::create('ppe_physical_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_count_id')->constrained('ppe_physical_counts')->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('article');
            $table->text('description')->nullable();
            $table->string('property_number');
            $table->string('unit_of_measure');
            $table->decimal('unit_value', 14, 2)->default(0);
            $table->integer('quantity_property_card')->default(0);
            $table->integer('quantity_physical_count')->default(0);
            $table->integer('shortage_quantity')->default(0);
            $table->decimal('shortage_value', 14, 2)->default(0);
            $table->integer('overage_quantity')->default(0);
            $table->decimal('overage_value', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('property_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppe_physical_count_items');
        Schema::dropIfExists('ppe_physical_counts');
    }
};
