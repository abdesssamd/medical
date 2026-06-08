<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plans', function (Blueprint $table): void {
            $table->string('signature_channel', 20)->nullable()->after('status');
            $table->string('signature_recipient', 191)->nullable()->after('signature_channel');
            $table->string('signature_token', 120)->nullable()->unique()->after('signature_recipient');
            $table->timestamp('signature_requested_at')->nullable()->after('signature_token');
            $table->timestamp('signature_expires_at')->nullable()->after('signature_requested_at');
            $table->timestamp('signed_at')->nullable()->after('signature_expires_at');
            $table->string('signed_by_patient_name', 191)->nullable()->after('signed_at');
            $table->string('signature_ip', 64)->nullable()->after('signed_by_patient_name');
            $table->text('signature_payload')->nullable()->after('signature_ip');
        });

        Schema::table('patient_journeys', function (Blueprint $table): void {
            $table->string('public_tracking_code', 80)->nullable()->unique()->after('invoice_id');
            $table->unsignedInteger('estimated_wait_minutes')->nullable()->after('public_tracking_code');
            $table->string('assigned_room_label', 120)->nullable()->after('estimated_wait_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('patient_journeys', function (Blueprint $table): void {
            $table->dropColumn([
                'public_tracking_code',
                'estimated_wait_minutes',
                'assigned_room_label',
            ]);
        });

        Schema::table('treatment_plans', function (Blueprint $table): void {
            $table->dropColumn([
                'signature_channel',
                'signature_recipient',
                'signature_token',
                'signature_requested_at',
                'signature_expires_at',
                'signed_at',
                'signed_by_patient_name',
                'signature_ip',
                'signature_payload',
            ]);
        });
    }
};

