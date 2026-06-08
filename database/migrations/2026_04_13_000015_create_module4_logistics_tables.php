<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sterilization_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_code')->unique();
            $table->timestamp('sterilized_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('sterilizer_cycle')->nullable();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('sterile'); // sterile, expired, quarantined
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('sterilization_pouches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('sterilization_batches')->cascadeOnDelete();
            $table->string('pouch_code')->unique();
            $table->string('instrument_set_name')->nullable();
            $table->string('status')->default('available'); // available, used, expired, quarantined
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });

        Schema::create('patient_sterilization_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('clinical_procedure_id')->nullable()->constrained('clinical_procedures')->nullOnDelete();
            $table->foreignId('sterilization_pouch_id')->constrained('sterilization_pouches')->cascadeOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scanned_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'scanned_at']);
            $table->index(['appointment_id', 'scanned_at']);
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('consumable'); // consumable, high_value
            $table->boolean('is_high_value')->default(false);
            $table->string('unit')->default('unit');
            $table->decimal('current_quantity', 12, 2)->default(0);
            $table->decimal('minimum_quantity', 12, 2)->default(0);
            $table->decimal('reorder_quantity', 12, 2)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category', 'is_active']);
            $table->index(['is_high_value', 'is_active']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->cascadeOnDelete();
            $table->string('type'); // in, out, adjustment, reserve, release
            $table->decimal('quantity', 12, 2);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->index(['type', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('clinical_procedure_id')->nullable()->constrained('clinical_procedures')->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lab_name');
            $table->string('lab_contact')->nullable();
            $table->string('order_number')->unique();
            $table->string('type')->default('prosthesis'); // crown, implant, ortho, prosthesis, other
            $table->string('status')->default('created'); // created, sent, in_progress, delivered, fitted, cancelled
            $table->date('due_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('external_file_paths')->nullable(); // JSON array text
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_date']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('patient_sterilization_traces');
        Schema::dropIfExists('sterilization_pouches');
        Schema::dropIfExists('sterilization_batches');
    }
};

