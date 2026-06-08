<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('queue_ticket_id')->nullable()->after('consulted_at')->constrained('tickets')->nullOnDelete();
        });

        Schema::table('appointment_settings', function (Blueprint $table): void {
            $table->foreignId('queue_service_id')->nullable()->after('timezone')->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('queue_service_id');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('queue_ticket_id');
        });
    }
};
