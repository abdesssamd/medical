<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cash Sessions
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('initial_balance', 12, 2)->default(0);
            $table->decimal('theoretical_total', 12, 2)->default(0);
            $table->decimal('actual_total', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->text('variance_reason')->nullable();
            $table->string('status')->default('open'); // open, closed, reconciled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'opened_at']);
            $table->index(['status']);
        });

        // Cash Transactions
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('method'); // cash, card, check, bank_transfer, insurance
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable(); // cheque number, transaction ID
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['cash_session_id']);
            $table->index(['invoice_id']);
            $table->index(['recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_sessions');
    }
};
