<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Creates the `reassignment_audits` table which records
     * user account reassignments performed by administrators.
     *
     * Columns:
     * - id: primary key
     * - user_id: the user that was reassigned
     * - actor_id: the admin who performed the reassignment (nullable)
     * - before: JSON snapshot of important attributes before the change
     * - after: JSON snapshot of important attributes after the change
     * - notes: free-form notes stored by the admin (nullable)
     * - ip_address: optional IP address where the action originated
     * - user_agent: optional user-agent string for the action
     * - created_at / updated_at
     *
     * The JSON `before` and `after` payloads should contain at minimum:
     *  - name, email, school_id, roles
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('reassignment_audits', function (Blueprint $table): void {
            $table->id();

            // The user whose account was reassigned
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // The admin/user who performed the reassignment (nullable)
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Snapshots of the record state before and after the change.
            // Keep nullable to allow for partial data in legacy or programmatic updates.
            $table->json('before')->nullable();
            $table->json('after')->nullable();

            // Optional administrative notes (reason, comment)
            $table->text('notes')->nullable();

            // Helpful metadata for auditing
            $table->string('ip_address', 45)->nullable(); // IPv6-compatible length
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Querying helpers
            $table->index(['user_id']);
            $table->index(['actor_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('reassignment_audits');
    }
};
