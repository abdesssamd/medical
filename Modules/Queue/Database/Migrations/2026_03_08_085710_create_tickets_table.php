<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transferred_to_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->date('ticket_date');
            $table->unsignedInteger('sequence_number');
            $table->string('ticket_number', 15);
            $table->enum('status', ['waiting', 'called', 'served', 'absent', 'transferred'])->default('waiting');
            $table->unsignedSmallInteger('estimated_wait_minutes')->default(0);
            $table->timestamp('arrived_at');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();
            $table->unique(['service_id', 'ticket_date', 'sequence_number']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
