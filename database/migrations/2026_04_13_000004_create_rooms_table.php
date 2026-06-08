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
        $appointmentRoomIdColumnExists = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'appointments')
            ->where('COLUMN_NAME', 'room_id')
            ->exists();

        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('code')->unique(); // R1, R2, CHIR1
                $table->string('type')->default('cabinet'); // cabinet, chirurgie, radio, ortho
                $table->text('equipment')->nullable(); // ["fauteuil", "radio", "cbct"]
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['organization_id', 'is_active']);
            });
        }

        // Junction table for practitioners and rooms
        if (! Schema::hasTable('practitioner_rooms')) {
            Schema::create('practitioner_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'room_id']);
            });
        }

        if (Schema::hasTable('appointments') && ! $appointmentRoomIdColumnExists) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('room_id')->nullable();
            });

            $appointmentRoomIdColumnExists = true;
        }

        $roomConstraintExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'appointments')
            ->where('CONSTRAINT_NAME', 'appointments_room_id_foreign')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (Schema::hasTable('appointments') && $appointmentRoomIdColumnExists && ! $roomConstraintExists) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practitioner_rooms');
        Schema::dropIfExists('rooms');
    }
};

