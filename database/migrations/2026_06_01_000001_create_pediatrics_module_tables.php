<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birth_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();

            $table->string('delivery_type', 30)->nullable();
            $table->string('delivery_place', 100)->nullable();
            $table->unsignedTinyInteger('gestational_age_weeks')->nullable();
            $table->string('presentation_at_birth', 30)->nullable();

            $table->unsignedTinyInteger('apgar_1min')->nullable();
            $table->unsignedTinyInteger('apgar_5min')->nullable();
            $table->unsignedTinyInteger('apgar_10min')->nullable();

            $table->decimal('birth_weight_grams', 7, 0)->nullable();
            $table->decimal('birth_length_cm', 5, 1)->nullable();
            $table->decimal('birth_head_circumference_cm', 5, 1)->nullable();

            $table->boolean('neonatal_resuscitation')->default(false);
            $table->string('resuscitation_details', 200)->nullable();
            $table->boolean('nicu_admission')->default(false);
            $table->unsignedTinyInteger('nicu_days')->nullable();

            $table->boolean('jaundice')->default(false);
            $table->string('jaundice_type', 50)->nullable();
            $table->date('jaundice_onset_date')->nullable();
            $table->string('jaundice_treatment', 100)->nullable();

            $table->boolean('breastfeeding')->default(false);
            $table->string('feeding_type', 30)->nullable();

            $table->string('vitamin_k_given', 20)->nullable();
            $table->boolean('hepatitis_b_birth_dose')->default(false);
            $table->boolean('newborn_screening_done')->default(false);
            $table->string('newborn_screening_result', 100)->nullable();

            $table->text('maternal_complications')->nullable();
            $table->text('neonatal_complications')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('growth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();

            $table->date('measurement_date');
            $table->unsignedTinyInteger('age_months')->nullable();

            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('head_circumference_cm', 5, 1)->nullable();
            $table->decimal('arm_circumference_cm', 5, 1)->nullable();

            $table->decimal('weight_percentile', 5, 1)->nullable();
            $table->decimal('height_percentile', 5, 1)->nullable();
            $table->decimal('head_circumference_percentile', 5, 1)->nullable();
            $table->decimal('bmi', 5, 1)->nullable();
            $table->decimal('weight_for_height_percentile', 5, 1)->nullable();

            $table->string('nutritional_status', 30)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'measurement_date']);
        });

        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('disease', 200)->nullable();
            $table->string('disease_ar', 200)->nullable();
            $table->unsignedTinyInteger('recommended_age_months')->nullable();
            $table->unsignedTinyInteger('dose_number')->default(1);
            $table->unsignedTinyInteger('total_doses')->default(1);
            $table->string('route', 30)->nullable();
            $table->string('site', 30)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('vaccination_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();

            $table->date('scheduled_date')->nullable();
            $table->date('administered_date')->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('injection_site', 50)->nullable();

            $table->string('status', 20)->default('pending');
            $table->text('adverse_reaction')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'scheduled_date']);
            $table->index(['patient_id', 'vaccine_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccination_records');
        Schema::dropIfExists('vaccines');
        Schema::dropIfExists('growth_records');
        Schema::dropIfExists('birth_histories');
    }
};
