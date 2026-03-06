<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('full_name')->nullable(); // auto-generated
            $table->string('position');
            $table->string('department')->nullable();
            $table->string('ro_office')->nullable();
            $table->string('sdo_office')->nullable();
            $table->enum('employment_type', ['teaching', 'non-teaching'])->default('teaching');
            $table->string('email')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('mobile_1')->nullable();
            $table->string('mobile_2')->nullable();
            $table->date('date_hired')->nullable();
            $table->boolean('is_oic')->default(false);
            $table->string('oic_office')->nullable();
            $table->boolean('is_non_deped_funded')->default(false);
            $table->string('source_of_funds')->nullable();
            $table->enum('status', ['active', 'inactive', 'retired'])->default('active');
            $table->date('date_of_separation')->nullable();
            $table->string('cause_of_separation')->nullable();
            $table->string('detailed_from')->nullable();
            $table->string('detailed_to')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index('employment_type');
        });
    }

    public function down(): void { Schema::dropIfExists('employees'); }
};
