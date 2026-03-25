<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('region')->nullable();
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Update districts to link to divisions
        Schema::table('districts', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('name')->constrained('divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
        Schema::dropIfExists('divisions');
    }
};
