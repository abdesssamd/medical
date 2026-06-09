<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->string('planning_mode', 20)->default('by_specialist')
                  ->after('consultation_minutes')
                  ->comment('by_specialist|by_act|mixed');

            $table->foreignId('appointment_type_id')->nullable()
                  ->after('planning_mode')
                  ->constrained('appointment_types')
                  ->nullOnDelete();

            $table->dropUnique(['professional_id', 'day_of_week']);
            $table->unique(['professional_id', 'day_of_week', 'planning_mode', 'appointment_type_id'], 'uniq_prof_day_mode_act');
        });
    }

    public function down(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropUnique('uniq_prof_day_mode_act');
            $table->unique(['professional_id', 'day_of_week']);
            $table->dropColumn(['planning_mode', 'appointment_type_id']);
        });
    }
};
