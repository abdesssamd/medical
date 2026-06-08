<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->boolean('is_appointment')->default(false)->after('status');
            $table->timestamp('appointment_at')->nullable()->after('is_appointment');
            $table->string('public_code', 32)->nullable()->unique()->after('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropUnique('tickets_public_code_unique');
            $table->dropColumn(['is_appointment', 'appointment_at', 'public_code']);
        });
    }
};
