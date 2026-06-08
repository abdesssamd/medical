<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function hasColumn(string $table, string $column): bool
    {
        return ! empty(DB::select('SHOW COLUMNS FROM `'.$table.'` LIKE '.DB::connection()->getPdo()->quote($column)));
    }

    public function up(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table): void {
            if (! $this->hasColumn('availability_blocks', 'label')) {
                $table->string('label')->nullable()->after('type');
            }
        });

        Schema::table('appointments', function (Blueprint $table): void {
            if (! $this->hasColumn('appointments', 'planning_id')) {
                $table->foreignId('planning_id')->nullable()->after('appointment_type_id')->constrained('plannings')->nullOnDelete();
            }
        });

        Schema::table('patient_consultations', function (Blueprint $table): void {
            if (! $this->hasColumn('patient_consultations', 'planning_id')) {
                $table->foreignId('planning_id')->nullable()->after('appointment_id')->constrained('plannings')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_consultations', function (Blueprint $table): void {
            if ($this->hasColumn('patient_consultations', 'planning_id')) {
                $table->dropConstrainedForeignId('planning_id');
            }
        });

        Schema::table('appointments', function (Blueprint $table): void {
            if ($this->hasColumn('appointments', 'planning_id')) {
                $table->dropConstrainedForeignId('planning_id');
            }
        });

        Schema::table('availability_blocks', function (Blueprint $table): void {
            if ($this->hasColumn('availability_blocks', 'label')) {
                $table->dropColumn('label');
            }
        });
    }
};