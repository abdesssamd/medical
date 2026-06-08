<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->date('test_date');
            $table->longText('parameters');
            $table->unsignedBigInteger('practitioner_id')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
