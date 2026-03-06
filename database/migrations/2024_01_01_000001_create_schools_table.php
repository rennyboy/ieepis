<?php
// ─── 2024_01_01_000001_create_schools_table.php ───
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('school_code')->unique();
            $table->string('school_id_number')->nullable();
            $table->enum('governance_level', ['Central', 'Regional', 'SDO', 'School'])->default('School');
            $table->string('district')->nullable();
            $table->string('region')->nullable();
            $table->string('division')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('street')->nullable();
            $table->text('complete_address')->nullable();
            $table->string('legislative_district')->nullable();
            $table->string('psgc')->nullable();
            $table->string('head_name')->nullable();
            $table->string('head_email')->nullable();
            $table->string('head_mobile')->nullable();
            $table->string('admin_staff_name')->nullable();
            $table->string('admin_staff_email')->nullable();
            $table->string('admin_staff_mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('landline')->nullable();
            $table->string('mobile_1')->nullable();
            $table->string('mobile_2')->nullable();
            $table->string('logo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('travel_time_minutes')->nullable();
            $table->boolean('is_very_remote')->default(false);
            $table->string('is_gidca')->default('None');
            $table->text('recent_developments')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('network_administrator_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void { Schema::dropIfExists('schools'); }
};
