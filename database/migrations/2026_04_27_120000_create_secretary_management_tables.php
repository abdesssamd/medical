<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Secretary Tasks
        Schema::create('secretary_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('task_type'); // document_missing, payment_due, consent_pending, insurance_verify, info_incomplete
            $table->string('status')->default('open'); // open, in_progress, completed, cancelled
            $table->string('priority')->default('normal'); // critical, high, normal, low
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('metadata')->nullable(); // JSON payload: document_type, field_name, etc.
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['priority', 'status']);
        });

        // Secretary Notes
        Schema::create('secretary_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('tag'); // document_missing, insurance_verify, consent_pending, payment_issue, urgent, other
            $table->text('message');
            $table->string('priority')->default('normal'); // critical, high, normal
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'read_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretary_notes');
        Schema::dropIfExists('secretary_tasks');
    }
};
