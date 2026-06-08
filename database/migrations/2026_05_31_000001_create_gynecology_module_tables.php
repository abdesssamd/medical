<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gynecological_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();

            $table->unsignedTinyInteger('gestity')->default(0);
            $table->unsignedTinyInteger('parity')->default(0);
            $table->unsignedTinyInteger('abortions')->default(0);
            $table->unsignedTinyInteger('living_children')->default(0);
            $table->unsignedTinyInteger('cesarean_sections')->default(0);
            $table->unsignedTinyInteger('ectopic_pregnancies')->default(0);

            $table->unsignedTinyInteger('menarche_age')->nullable();
            $table->unsignedTinyInteger('menopause_age')->nullable();
            $table->unsignedTinyInteger('cycle_duration_days')->nullable();
            $table->unsignedTinyInteger('menstruation_duration_days')->nullable();
            $table->string('cycle_regularity', 20)->default('regular');
            $table->string('contraception_method', 100)->nullable();
            $table->date('last_menstrual_period')->nullable();
            $table->date('last_fcv_date')->nullable();
            $table->string('last_fcv_result', 100)->nullable();

            $table->longText('family_history_cancers')->nullable();
            $table->longText('gynecological_conditions')->nullable();
            $table->longText('obstetric_complications_history')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('pregnancy_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('gynecological_history_id')->nullable()->constrained()->nullOnDelete();

            $table->string('pregnancy_number', 10)->nullable();
            $table->date('lmp_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('corrected_delivery_date')->nullable();
            $table->unsignedTinyInteger('gestational_age_weeks')->nullable();
            $table->unsignedTinyInteger('gestational_age_days')->nullable();
            $table->string('trimester', 10)->nullable();

            $table->string('pregnancy_status', 30)->default('active');
            $table->string('risk_level', 20)->default('low');
            $table->longText('risk_factors')->nullable();

            $table->string('blood_type', 10)->nullable();
            $table->string('rh_factor', 5)->nullable();
            $table->string('partner_blood_type', 10)->nullable();
            $table->string('partner_rh_factor', 5)->nullable();

            $table->string('serology_hiv', 20)->nullable();
            $table->string('serology_hepatitis_b', 20)->nullable();
            $table->string('serology_hepatitis_c', 20)->nullable();
            $table->string('serology_syphilis', 20)->nullable();
            $table->string('serology_toxoplasmosis', 20)->nullable();
            $table->string('serology_rubella', 20)->nullable();
            $table->string('serology_cmV', 20)->nullable();

            $table->string('blood_group_rh', 20)->nullable();
            $table->string('rai_result', 30)->nullable();
            $table->string('og_sullivan_result', 50)->nullable();
            $table->string('streptococcus_b_result', 30)->nullable();

            $table->date('delivery_date')->nullable();
            $table->string('delivery_mode', 30)->nullable();
            $table->unsignedTinyInteger('delivery_gestational_weeks')->nullable();
            $table->string('newborn_sex', 10)->nullable();
            $table->unsignedInteger('newborn_weight_grams')->nullable();
            $table->unsignedSmallInteger('newborn_height_cm')->nullable();
            $table->unsignedSmallInteger('apgar_1min')->nullable();
            $table->unsignedSmallInteger('apgar_5min')->nullable();
            $table->unsignedSmallInteger('apgar_10min')->nullable();
            $table->text('delivery_notes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'pregnancy_status']);
        });

        Schema::create('prenatal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('visit_date');
            $table->unsignedTinyInteger('visit_number')->nullable();
            $table->unsignedTinyInteger('gestational_weeks_at_visit')->nullable();
            $table->unsignedTinyInteger('gestational_days_at_visit')->nullable();

            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('blood_pressure_systolic', 5, 0)->nullable();
            $table->decimal('blood_pressure_diastolic', 5, 0)->nullable();
            $table->decimal('fundal_height_cm', 5, 1)->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->string('fetal_presentation', 30)->nullable();
            $table->string('fetal_position', 30)->nullable();
            $table->string('fetal_movements', 20)->default('present');

            $table->string('urine_protein', 20)->nullable();
            $table->string('urine_glucose', 20)->nullable();
            $table->string('edema', 20)->default('absent');
            $table->string('cervical_status', 50)->nullable();

            $table->longText('prescribed_exams')->nullable();
            $table->longText('prescribed_supplements')->nullable();
            $table->text('observations')->nullable();
            $table->text('recommendations')->nullable();

            $table->timestamps();
            $table->index(['pregnancy_record_id', 'visit_date']);
        });

        Schema::create('gynecological_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('pregnancy_record_id')->nullable()->constrained()->nullOnDelete();

            $table->date('exam_date');
            $table->string('exam_type', 50);

            $table->string('fcv_type', 30)->nullable();
            $table->string('fcv_result', 100)->nullable();
            $table->string('fcv_bethesda_classification', 50)->nullable();
            $table->date('fcv_sample_date')->nullable();
            $table->string('fcv_sample_quality', 30)->nullable();
            $table->boolean('hpv_test_done')->default(false);
            $table->string('hpv_result', 30)->nullable();
            $table->string('hpv_genotype', 50)->nullable();

            $table->longText('breast_exam_findings')->nullable();
            $table->string('breast_exam_conclusion', 50)->nullable();
            $table->date('last_mammography_date')->nullable();
            $table->string('mammography_result', 100)->nullable();

            $table->longText('vaginal_exam_findings')->nullable();
            $table->string('cervix_appearance', 50)->nullable();
            $table->string('cervix_consistency', 30)->nullable();
            $table->string('cervix_position', 30)->nullable();
            $table->string('cervix_dilation_cm', 10)->nullable();
            $table->string('cervix_effacement_percent', 10)->nullable();
            $table->string('uterus_size', 50)->nullable();
            $table->string('uterus_position', 30)->nullable();
            $table->string('uterus_mobility', 30)->nullable();
            $table->longText('adnexal_findings')->nullable();
            $table->string('douglas_pouch', 30)->nullable();

            $table->longText('pelvimetry')->nullable();

            $table->text('conclusion')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('follow_up_plan', 100)->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'exam_type', 'exam_date']);
        });

        Schema::create('ultrasound_biometries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pregnancy_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();

            $table->date('exam_date');
            $table->string('ultrasound_type', 30)->default('obstetric');
            $table->unsignedTinyInteger('trimester')->nullable();
            $table->string('exam_indication', 100)->nullable();

            $table->string('fetal_presentation', 30)->nullable();
            $table->string('fetal_position', 30)->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->string('fetal_movements', 20)->default('present');
            $table->unsignedTinyInteger('fetus_count')->default(1);

            $table->decimal('bip_mm', 6, 1)->nullable();
            $table->decimal('hc_mm', 7, 1)->nullable();
            $table->decimal('ac_mm', 7, 1)->nullable();
            $table->decimal('fl_mm', 6, 1)->nullable();
            $table->decimal('efw_grams', 7, 0)->nullable();
            $table->unsignedTinyInteger('efw_percentile')->nullable();

            $table->decimal('amniotic_fluid_index_mm', 6, 1)->nullable();
            $table->string('amniotic_fluid_assessment', 30)->nullable();
            $table->string('placenta_location', 30)->nullable();
            $table->string('placenta_grade', 10)->nullable();
            $table->unsignedSmallInteger('placenta_distance_from_os_mm')->nullable();

            $table->string('umbilical_artery_pi', 20)->nullable();
            $table->string('umbilical_artery_ri', 20)->nullable();
            $table->string('umbilical_artery_sd_ratio', 20)->nullable();
            $table->string('middle_cerebral_artery_pi', 20)->nullable();
            $table->string('ductus_venosus_pi', 20)->nullable();

            $table->unsignedTinyInteger('crl_mm')->nullable();
            $table->string('nt_mm', 10)->nullable();
            $table->string('nasal_bone', 20)->nullable();

            $table->string('fetal_sex', 10)->nullable();
            $table->longText('morphological_findings')->nullable();
            $table->boolean('structural_anomaly_detected')->default(false);
            $table->text('anomaly_description')->nullable();

            $table->string('cervical_length_mm', 10)->nullable();
            $table->longText('ovarian_findings')->nullable();
            $table->longText('uterine_findings')->nullable();

            $table->text('conclusion')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('follow_up_plan', 200)->nullable();
            $table->string('image_path', 500)->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'exam_date']);
            $table->index(['pregnancy_record_id', 'trimester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ultrasound_biometries');
        Schema::dropIfExists('gynecological_exams');
        Schema::dropIfExists('prenatal_visits');
        Schema::dropIfExists('pregnancy_records');
        Schema::dropIfExists('gynecological_histories');
    }
};
