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
        Schema::table('patient_consultations', function (Blueprint $table): void {
            if (! $this->hasColumn('patient_consultations', 'consultation_reason')) {
                $table->string('consultation_reason')->nullable()->after('consultation_date');
            }

            if (! $this->hasColumn('patient_consultations', 'consultation_type')) {
                $table->string('consultation_type', 40)->nullable()->after('consultation_reason');
            }

            if (! $this->hasColumn('patient_consultations', 'consultation_status')) {
                $table->string('consultation_status', 32)->default('attendu')->after('consultation_type');
            }

            if (! $this->hasColumn('patient_consultations', 'observations')) {
                $table->longText('observations')->nullable()->after('anamnesis');
            }

            if (! $this->hasColumn('patient_consultations', 'diagnosis_code')) {
                $table->string('diagnosis_code', 80)->nullable()->after('diagnosis');
            }

            if (! $this->hasColumn('patient_consultations', 'diagnosis_label')) {
                $table->text('diagnosis_label')->nullable()->after('diagnosis_code');
            }

            if (! $this->hasColumn('patient_consultations', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('notes')->constrained('invoices')->nullOnDelete();
            }

            if (! $this->hasColumn('patient_consultations', 'payment_status')) {
                $table->string('payment_status', 24)->default('unbilled')->after('invoice_id');
            }

            if (! $this->hasColumn('patient_consultations', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }

            if (! $this->hasColumn('patient_consultations', 'source')) {
                $table->string('source', 40)->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_consultations', function (Blueprint $table): void {
            foreach (['source', 'paid_at', 'payment_status', 'invoice_id', 'diagnosis_label', 'diagnosis_code', 'observations', 'consultation_status', 'consultation_type', 'consultation_reason'] as $column) {
                if ($this->hasColumn('patient_consultations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};