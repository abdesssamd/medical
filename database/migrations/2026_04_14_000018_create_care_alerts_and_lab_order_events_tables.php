<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 60); // stock, sterilization, allergy, implant_recall
            $table->string('severity', 20)->default('warning'); // info, warning, critical
            $table->string('title');
            $table->text('message');
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('alerted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'severity', 'resolved_at']);
            $table->index(['patient_id', 'resolved_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('lab_order_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->string('event_type', 60); // created, sent, status_update, file_sent, lab_feedback
            $table->string('status', 40)->nullable();
            $table->text('message')->nullable();
            $table->text('meta')->nullable(); // JSON
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('event_at')->nullable();
            $table->timestamps();

            $table->index(['lab_order_id', 'event_at']);
            $table->index(['event_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_events');
        Schema::dropIfExists('care_alerts');
    }
};

