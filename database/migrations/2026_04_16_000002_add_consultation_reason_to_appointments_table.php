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
        Schema::table('appointments', function (Blueprint $table): void {
            if (! $this->hasColumn('appointments', 'consultation_reason')) {
                $table->string('consultation_reason')->nullable()->after('patient_email');
            }

            if (! $this->hasColumn('appointments', 'consultation_type')) {
                $table->string('consultation_type', 40)->nullable()->after('consultation_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            foreach (['consultation_type', 'consultation_reason'] as $column) {
                if ($this->hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
