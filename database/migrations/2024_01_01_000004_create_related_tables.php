<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Equipment Assignments
        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('new_accountable_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('assigned_at');
            $table->date('custodian_received_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->date('new_accountable_received_at')->nullable();
            $table->string('assigned_by')->nullable();
            $table->string('transaction_type')->default('Issuance');
            $table->string('supporting_doc_type')->nullable();
            $table->string('supporting_doc_no')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['equipment_id', 'returned_at']);
            $table->index(['employee_id', 'returned_at']);
        });

        // Documents
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type'); // PAR, ICS, IAR, DR, OR, SI, WMR, RRSP, RRPE
            $table->string('document_no')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('document_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'document_type']);
        });

        // Tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('issue_title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in-progress', 'pending', 'resolved', 'closed'])->default('open');
            $table->foreignId('assigned_to_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['status', 'priority']);
        });

        // Internet Connections
        Schema::create('internet_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('isp');
            $table->string('account_number')->nullable();
            $table->string('plan_name')->nullable();
            $table->decimal('contracted_download_speed', 8, 2)->nullable();
            $table->decimal('contracted_upload_speed', 8, 2)->nullable();
            $table->decimal('actual_download_speed', 8, 2)->nullable();
            $table->decimal('actual_upload_speed', 8, 2)->nullable();
            $table->unsignedSmallInteger('latency_ms')->nullable();
            $table->date('speed_test_date')->nullable();
            $table->string('ip_address')->nullable();
            $table->enum('connection_type', ['Fiber', 'DSL', 'Wireless', 'LTE', 'Satellite', 'Others'])->default('Fiber');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->decimal('monthly_cost', 10, 2)->nullable();
            $table->date('subscription_start')->nullable();
            $table->date('subscription_end')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internet_connections');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('equipment_assignments');
    }
};
