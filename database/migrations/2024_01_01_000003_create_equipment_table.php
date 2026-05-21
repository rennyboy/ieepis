<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('property_no')->unique();
            $table->string('old_property_no')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('item_type')->nullable();        // Device Type / Equipment / Hardware / Software / Peripherals
            $table->string('equipment_type')->nullable();   // Laptop, Desktop, etc.
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('specifications')->nullable();
            $table->string('unit_of_measure')->default('Unit');
            $table->enum('category', ['High-Value', 'Low-Value'])->default('High-Value');
            $table->string('classification')->default('Machinery and Equipment for ICT');
            $table->string('gl_sl_code')->nullable();
            $table->string('uacs_code')->nullable();
            $table->boolean('is_dcp')->default(false);
            $table->string('dcp_package')->nullable();
            $table->year('dcp_year')->nullable();
            $table->boolean('is_non_dcp')->default(false);
            $table->decimal('acquisition_cost', 12, 2)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('received_date')->nullable();
            $table->unsignedSmallInteger('estimated_useful_life')->nullable();
            $table->string('mode_of_acquisition')->nullable(); // Purchased / Donation / Grant
            $table->string('source_of_acquisition')->nullable();
            $table->string('donor')->nullable();
            $table->string('source_of_funds')->nullable();
            $table->string('pmp_reference_no')->nullable();
            $table->string('supporting_doc_type_acquisition')->nullable(); // OR / SI / DR / IAR / RRSP
            $table->string('supporting_doc_no_acquisition')->nullable();
            $table->string('supplier')->nullable();
            $table->boolean('under_warranty')->default(false);
            $table->date('warranty_end_date')->nullable();
            $table->string('equipment_location')->nullable();
            $table->boolean('is_functional')->default(true);
            $table->enum('condition', ['Good', 'Fair', 'Poor', 'Unserviceable'])->default('Good');
            $table->string('accountability_status')->default('unassigned');
            $table->string('disposition_status')->nullable();
            $table->text('remarks')->nullable();
            $table->text('qr_code')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('supporting_doc_type_issuance')->nullable(); // PAR / ICS / RRSP / RRPE / WMR
            $table->string('supporting_doc_no_issuance')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'accountability_status']);
            $table->index(['is_dcp', 'condition']);
            $table->index('equipment_type');
        });
    }

    public function down(): void { Schema::dropIfExists('equipment'); }
};
