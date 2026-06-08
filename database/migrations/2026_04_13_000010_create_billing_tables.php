<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insurance Companies
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // CNAS, CASNOS, etc.
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->text('coverage_rules')->nullable(); // {implants: 0.5, crowns: 0.7}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Patient Insurance Subscriptions
        Schema::create('patient_insurance_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->string('policy_number')->unique();
            $table->string('group_number')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique(); // FAC-2026-0001
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft'); // draft, sent, partially_paid, paid, cancelled, refunded
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0); // Tax percentage
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->text('payment_methods')->nullable(); // [{method: "cash", amount: 500}, {method: "card", amount: 300}]
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['invoice_date', 'status']);
        });

        // Invoice Line Items
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('clinical_procedures')->nullOnDelete();
            $table->string('description');
            $table->string('procedure_code')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        // Insurance Claims
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_insurance_id')->nullable()->constrained('patient_insurance_subscriptions')->nullOnDelete();
            $table->string('claim_number')->unique(); // CLM-2026-0001
            $table->string('external_reference')->nullable(); // Reference from insurance company
            $table->decimal('claimed_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('rejected_amount', 10, 2)->nullable();
            $table->decimal('patient_remaining', 10, 2)->nullable(); // Reste à charge
            $table->string('status')->default('pending'); // pending, submitted, under_review, approved, partially_paid, paid, rejected
            $table->date('submitted_at')->nullable();
            $table->date('response_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('line_items')->nullable(); // Details of what was approved/rejected
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['insurance_company_id', 'status']);
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_claim_id')->nullable()->constrained('insurance_claims')->nullOnDelete();
            $table->string('payment_number')->unique(); // PAY-2026-0001
            $table->string('method'); // cash, card, check, bank_transfer, insurance
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MAD');
            $table->string('reference')->nullable(); // Check number, transaction ID
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['invoice_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('patient_insurance_subscriptions');
        Schema::dropIfExists('insurance_companies');
    }
};

