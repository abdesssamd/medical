<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasColumn(string $table, string $column): bool
    {
        return ! empty(DB::select('SHOW COLUMNS FROM `'.$table.'` LIKE '.DB::connection()->getPdo()->quote($column)));
    }

    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            if (! $this->hasColumn('patients', 'patient_photo_path')) {
                $table->string('patient_photo_path')->nullable()->after('medical_record_number');
            }

            if (! $this->hasColumn('patients', 'blood_group')) {
                $table->string('blood_group', 5)->nullable()->after('gender');
            }

            if (! $this->hasColumn('patients', 'height_cm')) {
                $table->decimal('height_cm', 5, 2)->nullable()->after('blood_group');
            }

            if (! $this->hasColumn('patients', 'weight_kg')) {
                $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');
            }

            if (! $this->hasColumn('patients', 'critical_conditions')) {
                $table->text('critical_conditions')->nullable()->after('current_medications');
            }

            if (! $this->hasColumn('patients', 'family_history')) {
                $table->text('family_history')->nullable()->after('critical_conditions');
            }

            if (! $this->hasColumn('patients', 'personal_history')) {
                $table->text('personal_history')->nullable()->after('family_history');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            foreach ([
                'personal_history',
                'family_history',
                'critical_conditions',
                'weight_kg',
                'height_cm',
                'blood_group',
                'patient_photo_path',
            ] as $column) {
                if ($this->hasColumn('patients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
