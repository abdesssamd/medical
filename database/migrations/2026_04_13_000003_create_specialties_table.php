<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // OMNI, ORTHO, CHIR, ENDO
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('default_color')->default('#3b82f6'); // For dental chart
            $table->integer('default_duration_minutes')->default(30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Junction table for users and specialties
        Schema::create('practitioner_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->integer('priority_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'specialty_id']);
            $table->index(['specialty_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practitioner_specialties');
        Schema::dropIfExists('specialties');
    }
};
