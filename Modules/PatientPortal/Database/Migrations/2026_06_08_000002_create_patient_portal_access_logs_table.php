<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_portal_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_portal_access_id')->constrained('patient_portal_accesses')->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['patient_portal_access_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_portal_access_logs');
    }
};
