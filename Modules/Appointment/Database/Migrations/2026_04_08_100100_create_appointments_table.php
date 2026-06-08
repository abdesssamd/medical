<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('secretary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name');
            $table->string('patient_phone', 30)->nullable();
            $table->string('patient_email')->nullable();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('booked'); // booked, cancelled, consulted, no_show
            $table->text('notes')->nullable();
            $table->dateTime('consulted_at')->nullable();
            $table->timestamps();

            $table->unique(['professional_id', 'appointment_date', 'start_time'], 'uniq_pro_date_time');
            $table->index(['professional_id', 'appointment_date', 'status']);
            $table->index(['secretary_id', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
