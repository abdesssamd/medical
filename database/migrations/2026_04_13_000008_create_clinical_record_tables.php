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
        // Dental Charts (Odontogramme)
        Schema::create('dental_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('version')->default('adult'); // adult, child
            $table->text('teeth_status'); // See documentation for structure
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'version']);
        });

        // Clinical Procedures (Actes cliniques)
        Schema::create('clinical_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('practitioner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tooth_number')->nullable(); // 11-48 or null if not tooth-specific
            $table->string('procedure_code'); // D0120, D2750 (ADA codes)
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('tooth_surfaces')->nullable(); // ["mesial", "distal", "occlusal"]
            $table->decimal('price', 10, 2);
            $table->string('status')->default('planned'); // planned, in_progress, completed, cancelled
            $table->date('planned_date')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('materials_used')->nullable(); // [{implant: "Straumann 4.1", lot: "LOT123"}]
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['appointment_id']);
            $table->index(['practitioner_id', 'performed_at']);
        });

        // Treatment Plans
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('objective')->nullable();
            $table->string('status')->default('draft'); // draft, approved, in_progress, completed, archived
            $table->decimal('total_estimated_cost', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('estimated_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->text('phases'); // [{name: "Assainissement", procedures: [...], order: 1}]
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        // Treatment Plan Procedures (junction)
        Schema::create('treatment_plan_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_id')->constrained('clinical_procedures')->cascadeOnDelete();
            $table->integer('phase_number');
            $table->integer('order_in_phase');
            $table->string('status')->default('planned'); // planned, scheduled, completed, skipped
            $table->timestamps();

            $table->index(
                ['treatment_plan_id', 'phase_number', 'order_in_phase'],
                'tp_proc_plan_phase_order_idx'
            );
        });

        // Medical Images (X-Rays, CBCT, STL, Photos)
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('clinical_procedures')->nullOnDelete();
            $table->string('type'); // xray, cbct, intraoral_photo, stl_scan, dicom
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('file_size')->nullable();
            $table->string('dicom_uid')->nullable();
            $table->text('associated_teeth')->nullable(); // [18, 17, 16]
            $table->string('taken_by')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'type']);
            $table->index(['patient_id', 'taken_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_procedures');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('clinical_procedures');
        Schema::dropIfExists('medical_images');
        Schema::dropIfExists('dental_charts');
    }
};

