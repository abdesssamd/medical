<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rehab_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('diagnosis');
            $table->unsignedInteger('prescribed_sessions_count');
            $table->text('objectives')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status'], 'rehab_presc_pat_status_idx');
            $table->index('doctor_id', 'rehab_presc_doctor_idx');
        });

        Schema::create('rehab_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('rehab_prescriptions')->onDelete('cascade');
            $table->enum('type', ['initial', 'intermediate', 'final'])->default('initial');
            $table->date('evaluation_date');
            $table->longText('goniometry')->nullable();
            $table->longText('muscle_testing')->nullable();
            $table->longText('functional_tests')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id', 'type'], 'rehab_eval_presc_type_idx');
        });

        Schema::create('rehab_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('rehab_prescriptions')->onDelete('cascade');
            $table->foreignId('physiotherapist_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('session_number');
            $table->date('session_date');
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->text('notes')->nullable();
            $table->longText('exercises_performed')->nullable();
            $table->enum('status', ['planned', 'completed', 'cancelled', 'missed'])->default('planned');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id', 'session_number'], 'rehab_sess_presc_num_idx');
            $table->index('physiotherapist_id', 'rehab_sess_physio_idx');
            $table->index('session_date', 'rehab_sess_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rehab_sessions');
        Schema::dropIfExists('rehab_evaluations');
        Schema::dropIfExists('rehab_prescriptions');
    }
};
