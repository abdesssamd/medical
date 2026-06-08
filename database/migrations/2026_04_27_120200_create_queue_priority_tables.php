<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Queue Priorities
        Schema::create('queue_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('priority_level')->default('normal'); // critical, high, normal, low
            $table->text('override_reason')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->integer('position')->default(0); // manual position in queue
            $table->timestamps();

            $table->index(['appointment_id']);
            $table->index(['priority_level']);
            $table->index(['overridden_at']);
        });

        // Add columns to appointments table for KPI tracking
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('consulted_at');
            $table->timestamp('ready_for_checkout_at')->nullable()->after('checked_in_at');
            $table->timestamp('checked_out_at')->nullable()->after('ready_for_checkout_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'ready_for_checkout_at', 'checked_out_at']);
        });
        Schema::dropIfExists('queue_priorities');
    }
};
