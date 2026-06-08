<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_image_id')->nullable()->constrained('medical_images')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('modality'); // xray, cbct, stl, dicom
            $table->string('study_uid')->nullable();
            $table->string('series_uid')->nullable();
            $table->string('instance_uid')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'modality']);
            $table->index(['study_uid', 'series_uid']);
            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_studies');
    }
};

