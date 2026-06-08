<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('treatment_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('quote_number', 40)->unique();
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('insurance_rate', 5, 2)->default(0);
            $table->decimal('insurance_amount', 12, 2)->default(0);
            $table->decimal('mutual_amount', 12, 2)->default(0);
            $table->decimal('patient_amount', 12, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->string('consent_status', 30)->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_patient_name')->nullable();
            $table->text('signature_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'status']);
        });

        Schema::create('treatment_quote_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('clinical_procedures')->nullOnDelete();
            $table->string('code', 60)->nullable();
            $table->string('label');
            $table->integer('phase_number')->default(1);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('insurance_coverage_rate', 5, 2)->default(0);
            $table->decimal('insurance_share', 12, 2)->default(0);
            $table->decimal('patient_share', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('teletransmission_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_number', 60)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('generated_on');
            $table->string('status', 30)->default('generated');
            $table->integer('invoice_count')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_recovery_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 20); // sms,email,call
            $table->string('status', 20)->default('sent');
            $table->text('message')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['invoice_id', 'performed_at']);
        });

        Schema::create('recall_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('label');
            $table->string('trigger_type', 40); // procedure_code, keyword, implant
            $table->string('trigger_value')->nullable();
            $table->integer('interval_days')->default(180);
            $table->boolean('is_active')->default(true);
            $table->text('channels')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_recalls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('recall_rule_id')->nullable()->constrained('recall_rules')->nullOnDelete();
            $table->string('reason', 160);
            $table->date('due_date');
            $table->string('status', 30)->default('pending');
            $table->date('last_notified_at')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'status', 'due_date']);
        });

        Schema::create('reminder_dispatch_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_recall_id')->nullable()->constrained('patient_recalls')->nullOnDelete();
            $table->string('channel', 20); // sms,email
            $table->string('context', 40); // appointment_24h, recall, unpaid
            $table->string('target')->nullable();
            $table->string('status', 20)->default('sent');
            $table->text('payload')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->index(['context', 'sent_at']);
        });

        Schema::create('periodontal_charts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('recorded_on');
            $table->text('teeth_measurements'); // JSON
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'recorded_on']);
        });

        Schema::create('orthodontic_photo_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->date('captured_on')->nullable();
            $table->string('before_image_path')->nullable();
            $table->string('after_image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'captured_on']);
        });

        Schema::create('patient_legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type', 50); // consent, questionnaire, id, legal
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('status', 30)->default('active');
            $table->date('signed_on')->nullable();
            $table->boolean('risk_flag')->default(false);
            $table->text('risk_summary')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'document_type']);
        });

        Schema::create('health_questionnaires', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('filled_on');
            $table->text('answers');
            $table->text('risk_tags')->nullable();
            $table->boolean('has_critical_risk')->default(false);
            $table->text('critical_notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'filled_on']);
        });

        Schema::create('ai_imaging_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imaging_study_id')->nullable()->constrained('imaging_studies')->nullOnDelete();
            $table->string('provider', 50)->default('orthanc_api');
            $table->string('analysis_type', 50); // caries_detection, bone_loss
            $table->string('status', 30)->default('queued');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('findings')->nullable();
            $table->text('raw_response')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'analysis_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_imaging_analyses');
        Schema::dropIfExists('health_questionnaires');
        Schema::dropIfExists('patient_legal_documents');
        Schema::dropIfExists('orthodontic_photo_sets');
        Schema::dropIfExists('periodontal_charts');
        Schema::dropIfExists('reminder_dispatch_logs');
        Schema::dropIfExists('patient_recalls');
        Schema::dropIfExists('recall_rules');
        Schema::dropIfExists('payment_recovery_actions');
        Schema::dropIfExists('teletransmission_batches');
        Schema::dropIfExists('treatment_quote_items');
        Schema::dropIfExists('treatment_quotes');
    }
};
