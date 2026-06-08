<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['call', 'recall', 'transfer']);
            $table->longText('voice_payload')->nullable();
            $table->timestamp('called_at');
            $table->timestamps();
            $table->index(['organization_id', 'called_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
