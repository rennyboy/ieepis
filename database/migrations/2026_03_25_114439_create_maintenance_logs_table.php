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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            
            $table->text('issue_description');
            $table->text('action_taken');
            
            $table->enum('status', ['resolved', 'repaired', 'replaced'])->default('resolved');
            
            $table->dateTime('date_performed');
            $table->timestamps();
            
            $table->index(['equipment_id', 'status', 'date_performed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
