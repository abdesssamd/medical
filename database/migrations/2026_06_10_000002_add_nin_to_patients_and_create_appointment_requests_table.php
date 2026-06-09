<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('nin', 50)->nullable()->after('medical_record_number')
                  ->comment('National Identification Number');
            $table->index('nin');
        });

        Schema::create('appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nin', 50)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained('appointment_types')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->date('preferred_date_from')->nullable();
            $table->date('preferred_date_to')->nullable();
            $table->string('time_preference', 20)->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('booked_at')->nullable();
            $table->timestamps();

            $table->index(['nin', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_requests');
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['nin']);
            $table->dropColumn('nin');
        });
    }
};
