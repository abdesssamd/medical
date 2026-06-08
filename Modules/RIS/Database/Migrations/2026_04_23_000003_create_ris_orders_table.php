<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RIS\Models\RisOrder;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ris_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('ris_procedures')->nullOnDelete();
            $table->foreignId('modality_id')->nullable()->constrained('ris_modalities')->nullOnDelete();
            // Compatibilite MySQL/MariaDB ancienne version: on evite le type JSON natif.
            $table->longText('orthanc_payload')->nullable();
            $table->enum('status', [
                RisOrder::STATUS_ORDONNE,
                RisOrder::STATUS_EN_ATTENTE,
                RisOrder::STATUS_IMAGES_RECUES,
                RisOrder::STATUS_TERMINE,
                RisOrder::STATUS_ANNULE,
            ])->default(RisOrder::STATUS_ORDONNE);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_orders');
    }
};
