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
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // IMPLANT, COURONNE, DETARTRAGE
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->integer('duration_minutes');
            $table->decimal('base_price', 10, 2)->nullable();
            $table->boolean('requires_follow_up')->default(false);
            $table->integer('follow_up_days')->nullable();
            $table->text('required_equipment')->nullable(); // ["fauteuil_2", "radio"]
            $table->text('required_material')->nullable(); // ["gant", "masque_chir"]
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['specialty_id', 'is_active']);
        });

        $database = DB::getDatabaseName();
        $constraintExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'appointments')
            ->where('CONSTRAINT_NAME', 'appointments_appointment_type_id_foreign')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (! $constraintExists) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('appointment_type_id')->references('id')->on('appointment_types')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};

