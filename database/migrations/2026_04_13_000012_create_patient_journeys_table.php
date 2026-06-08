<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('queue_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('current_status')->default('booked');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('in_care_at')->nullable();
            $table->timestamp('awaiting_payment_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('appointment_id');
            $table->index(['current_status', 'updated_at']);
            $table->index(['patient_id', 'current_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_journeys');
    }
};

