<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection()->getDriverName();

        // PostgreSQL requires an explicit USING clause for text -> json cast
        if ($connection === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json');
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->json('data')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->text('data')->change();
        });
    }
};
