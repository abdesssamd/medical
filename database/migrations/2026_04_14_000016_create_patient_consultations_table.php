<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('consultation_date');
            $table->text('chief_complaint')->nullable();
            $table->text('anamnesis')->nullable();
            $table->text('clinical_exam')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('prescription')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('vital_signs')->nullable(); // JSON text
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'consultation_date']);
            $table->index(['practitioner_id', 'consultation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_consultations');
    }
};

