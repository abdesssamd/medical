<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('burn_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();

            $table->string('burn_type', 30);
            $table->string('burn_cause', 200)->nullable();
            $table->dateTime('accident_datetime');
            $table->dateTime('admission_datetime')->nullable();
            $table->decimal('admission_weight_kg', 5, 2)->nullable();
            $table->decimal('admission_height_cm', 5, 1)->nullable();
            $table->string('admission_location', 100)->nullable();
            $table->text('mechanism_description')->nullable();
            $table->boolean('inhalation_injury_suspected')->default(false);
            $table->boolean('inhalation_injury_confirmed')->default(false);
            $table->string('inhalation_severity', 20)->nullable();
            $table->text('associated_injuries')->nullable();
            $table->string('admission_status', 30)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'admission_datetime']);
        });

        Schema::create('burn_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burn_admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('assessment_datetime');
            $table->decimal('total_burn_surface_area', 5, 2);
            $table->decimal('partial_thickness_area', 5, 2)->nullable();
            $table->decimal('full_thickness_area', 5, 2)->nullable();
            $table->decimal('superficial_area', 5, 2)->nullable();

            $table->longText('body_regions')->nullable();

            $table->string('head_face_percent', 5)->nullable();
            $table->string('neck_percent', 5)->nullable();
            $table->string('anterior_trunk_percent', 5)->nullable();
            $table->string('posterior_trunk_percent', 5)->nullable();
            $table->string('right_arm_percent', 5)->nullable();
            $table->string('left_arm_percent', 5)->nullable();
            $table->string('right_forearm_hand_percent', 5)->nullable();
            $table->string('left_forearm_hand_percent', 5)->nullable();
            $table->string('right_thigh_percent', 5)->nullable();
            $table->string('left_thigh_percent', 5)->nullable();
            $table->string('right_leg_foot_percent', 5)->nullable();
            $table->string('left_leg_foot_percent', 5)->nullable();
            $table->string('genitalia_percent', 5)->nullable();

            $table->string('depth_dominant', 30)->nullable();
            $table->boolean('circumferential_burns')->default(false);
            $table->text('circumferential_locations')->nullable();
            $table->boolean('escharotomy_needed')->default(false);
            $table->text('escharotomy_locations')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['burn_admission_id', 'assessment_datetime'], 'burn_assess_adm_dt_idx');
        });

        Schema::create('fluid_resuscitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burn_admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('patient_weight_kg', 5, 2);
            $table->decimal('burn_surface_area_percent', 5, 2);
            $table->string('formula_used', 30)->default('parkland');

            $table->decimal('total_volume_ml', 8, 2);
            $table->decimal('first_8h_volume_ml', 8, 2);
            $table->decimal('next_16h_volume_ml', 8, 2);
            $table->decimal('first_8h_rate_ml_per_hour', 6, 2);
            $table->decimal('next_16h_rate_ml_per_hour', 6, 2);

            $table->dateTime('resuscitation_start_time');
            $table->dateTime('first_8h_end_time');
            $table->dateTime('next_16h_end_time');

            $table->string('fluid_type', 50)->default('ringer_lactate');
            $table->decimal('maintenance_fluid_ml_per_hour', 6, 2)->nullable();

            $table->decimal('urine_output_target_ml_per_hour', 6, 2)->nullable();
            $table->decimal('actual_urine_output_ml', 8, 2)->nullable();

            $table->string('status', 20)->default('active');
            $table->text('adjustments_notes')->nullable();

            $table->timestamps();
            $table->index(['burn_admission_id', 'resuscitation_start_time'], 'fluid_resc_adm_start_idx');
        });

        Schema::create('wound_evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burn_admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('burn_assessment_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('evolution_datetime');
            $table->string('body_region', 100);
            $table->string('wound_status', 30);
            $table->string('depth_current', 30)->nullable();
            $table->text('wound_description')->nullable();

            $table->boolean('graft_planned')->default(false);
            $table->dateTime('graft_planned_date')->nullable();
            $table->string('graft_type', 50)->nullable();
            $table->string('graft_donor_site', 100)->nullable();
            $table->boolean('graft_completed')->default(false);
            $table->dateTime('graft_completed_date')->nullable();
            $table->string('graft_outcome', 30)->nullable();

            $table->boolean('flap_planned')->default(false);
            $table->string('flap_type', 100)->nullable();
            $table->dateTime('flap_planned_date')->nullable();

            $table->string('dressing_type', 100)->nullable();
            $table->text('dressing_instructions')->nullable();
            $table->integer('dressing_change_frequency_hours')->nullable();

            $table->boolean('pharmacy_order_needed')->default(false);
            $table->boolean('pharmacy_order_sent')->default(false);
            $table->dateTime('pharmacy_order_sent_at')->nullable();
            $table->text('pharmacy_order_items')->nullable();

            $table->string('infection_signs', 100)->nullable();
            $table->boolean('infection_confirmed')->default(false);
            $table->string('infection_organism', 100)->nullable();

            $table->string('photo_path', 500)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['burn_admission_id', 'evolution_datetime'], 'wound_evo_adm_dt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wound_evolutions');
        Schema::dropIfExists('fluid_resuscitations');
        Schema::dropIfExists('burn_assessments');
        Schema::dropIfExists('burn_admissions');
    }
};
