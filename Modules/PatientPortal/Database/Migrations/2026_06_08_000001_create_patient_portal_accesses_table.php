<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_portal_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('ris_orders')->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('ris_reports')->nullOnDelete();
            $table->string('access_token', 80)->unique();
            $table->string('access_code_hash');
            $table->string('access_code_last4', 8)->nullable();
            $table->string('delivery_channel', 32)->default('manual');
            $table->string('delivery_email')->nullable();
            $table->string('delivery_phone', 32)->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('locked_until_at')->nullable();
            $table->dateTime('last_access_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->text('last_user_agent')->nullable();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['patient_id', 'expires_at']);
            $table->index(['patient_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('patient_portal_access_logs');
        Schema::dropIfExists('patient_portal_accesses');
        Schema::enableForeignKeyConstraints();
    }
};
