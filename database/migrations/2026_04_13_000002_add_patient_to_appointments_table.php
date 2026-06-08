<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $database = DB::getDatabaseName();

        $columnExists = static function (string $table, string $column) use ($database): bool {
            return DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->exists();
        };

        $foreignExists = static function (string $table, string $constraintName) use ($database): bool {
            return DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('CONSTRAINT_NAME', $constraintName)
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
        };

        if (! $columnExists('appointments', 'patient_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('patient_id')->nullable()->after('created_by');
            });
        }

        if (! $columnExists('appointments', 'appointment_type_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('appointment_type_id')->nullable()->after('patient_id');
            });
        }

        if (! $columnExists('appointments', 'parent_appointment_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('parent_appointment_id')->nullable()->after('appointment_type_id');
            });
        }

        if (! $columnExists('appointments', 'room_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('room_id')->nullable()->after('queue_ticket_id');
            });
        }

        if (! $columnExists('appointments', 'follow_up_status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('follow_up_status')->nullable()->after('status');
            });
        }

        if (! $columnExists('appointments', 'consultation_notes')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->text('consultation_notes')->nullable()->after('notes');
            });
        }

        if (! $foreignExists('appointments', 'appointments_patient_id_foreign')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            });
        }

        if (! $foreignExists('appointments', 'appointments_parent_appointment_id_foreign')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('parent_appointment_id')->references('id')->on('appointments')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['appointment_type_id']);
            $table->dropForeign(['parent_appointment_id']);
            $table->dropForeign(['room_id']);
            
            $table->dropColumn([
                'patient_id',
                'appointment_type_id',
                'parent_appointment_id',
                'room_id',
                'follow_up_status',
                'consultation_notes',
            ]);
        });
    }
};

