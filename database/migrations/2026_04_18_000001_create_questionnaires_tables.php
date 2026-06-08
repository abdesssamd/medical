<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('group_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // Use longText for compatibility with MySQL variants that do not support JSON.
            $table->longText('field_schema');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['specialty_id', 'is_active']);
            $table->index(['practitioner_id', 'is_active']);
            $table->index(['group_name', 'is_active']);
        });

        Schema::create('patient_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            // Use longText for compatibility with MySQL variants that do not support JSON.
            $table->longText('answers');
            $table->timestamp('answered_at');
            $table->text('notes')->nullable();
            $table->string('source', 40)->default('module3');
            $table->timestamps();

            $table->index(['patient_id', 'answered_at']);
            $table->index(['questionnaire_id', 'answered_at']);
            $table->index(['consultation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_responses');
        Schema::dropIfExists('questionnaires');
    }
};
