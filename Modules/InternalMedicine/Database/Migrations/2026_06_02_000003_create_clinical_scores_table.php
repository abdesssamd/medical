<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('score_type', 50);
            $table->decimal('calculated_value', 8, 2);
            $table->date('date');
            $table->longText('score_data')->nullable();
            $table->unsignedBigInteger('practitioner_id')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'score_type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_scores');
    }
};
