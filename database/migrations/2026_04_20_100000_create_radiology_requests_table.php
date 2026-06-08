<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prescribing_physician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('exam_type', 120);
            $table->string('anatomical_region', 120);
            $table->enum('priority', ['urgent', 'routine'])->default('routine');
            $table->text('clinical_reason');
            $table->string('scheduled_station_ae_title', 64)->default('MODALITY_AE');
            $table->string('target_modality', 16)->default('CT');
            $table->string('requested_procedure_description', 255);
            $table->string('accession_number', 50)->unique();
            $table->string('study_instance_uid', 128)->unique();
            $table->enum('workflow_status', ['requested', 'in_progress', 'received', 'completed'])->default('requested');
            $table->string('orthanc_worklist_id', 128)->nullable();
            $table->string('worklist_file_path', 500)->nullable();
            $table->longText('orthanc_payload')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'workflow_status']);
            $table->index(['accession_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_requests');
    }
};
