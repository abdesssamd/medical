<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category', 80)->nullable();
            $table->string('strength')->nullable();
            $table->longText('forms')->nullable();
            $table->string('default_unit', 50)->nullable();
            $table->string('default_frequency', 80)->nullable();
            $table->integer('default_duration_days')->nullable();
            $table->longText('allergen_keywords')->nullable();
            $table->longText('contraindication_tags')->nullable();
            $table->longText('interaction_keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['name', 'is_active']);
        });

        Schema::create('prescription_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('context', 120)->nullable();
            $table->longText('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('prescription_template_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('medications')->nullOnDelete();
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('unit', 60)->nullable();
            $table->string('frequency', 80)->nullable();
            $table->integer('duration_days')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prescription_template_id')->nullable()->constrained('prescription_templates')->nullOnDelete();
            $table->string('prescription_number', 60)->unique();
            $table->timestamp('issued_at');
            $table->string('status', 30)->default('issued');
            $table->string('qr_token', 80)->unique();
            $table->string('signature_mode', 30)->default('digital');
            $table->longText('signature_payload')->nullable();
            $table->longText('safety_alerts')->nullable();
            $table->longText('immutable_payload')->nullable();
            $table->string('sent_to_email')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'issued_at']);
        });

        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('medications')->nullOnDelete();
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('unit', 60)->nullable();
            $table->string('frequency', 80)->nullable();
            $table->integer('duration_days')->nullable();
            $table->text('instructions')->nullable();
            $table->string('interaction_level', 30)->default('ok');
            $table->longText('alerts')->nullable();
            $table->timestamps();
        });

        $now = now();

        $medications = [
            ['name' => 'Amoxicilline', 'category' => 'Antibiotique', 'strength' => '1 g', 'forms' => json_encode(['gelule', 'comprime']), 'default_unit' => 'gelule', 'default_frequency' => 'Matin/Midi/Soir', 'default_duration_days' => 7, 'allergen_keywords' => json_encode(['penicilline', 'amoxicilline']), 'contraindication_tags' => json_encode(['allergie_penicilline']), 'interaction_keywords' => json_encode(['methotrexate'])],
            ['name' => 'Clindamycine', 'category' => 'Antibiotique', 'strength' => '300 mg', 'forms' => json_encode(['gelule']), 'default_unit' => 'gelule', 'default_frequency' => 'Matin/Midi/Soir', 'default_duration_days' => 7, 'allergen_keywords' => json_encode([]), 'contraindication_tags' => json_encode(['colite']), 'interaction_keywords' => json_encode([])],
            ['name' => 'Ibuprofene', 'category' => 'Antalgique', 'strength' => '400 mg', 'forms' => json_encode(['comprime']), 'default_unit' => 'comprime', 'default_frequency' => 'Matin/Midi/Soir', 'default_duration_days' => 3, 'allergen_keywords' => json_encode(['ains']), 'contraindication_tags' => json_encode(['ulcere_gastrique', 'insuffisance_renale', 'anticoagulants']), 'interaction_keywords' => json_encode(['warfarine', 'anticoagulant'])],
            ['name' => 'Paracetamol', 'category' => 'Antalgique', 'strength' => '1 g', 'forms' => json_encode(['comprime', 'sachet']), 'default_unit' => 'comprime', 'default_frequency' => 'Matin/Midi/Soir', 'default_duration_days' => 3, 'allergen_keywords' => json_encode([]), 'contraindication_tags' => json_encode(['insuffisance_hepatique']), 'interaction_keywords' => json_encode([])],
            ['name' => 'Chlorhexidine', 'category' => 'Bain de bouche', 'strength' => '0.12%', 'forms' => json_encode(['bain_de_bouche']), 'default_unit' => 'pulverisation', 'default_frequency' => 'Matin/Soir', 'default_duration_days' => 10, 'allergen_keywords' => json_encode([]), 'contraindication_tags' => json_encode([]), 'interaction_keywords' => json_encode([])],
            ['name' => 'Articaine', 'category' => 'Anesthesique', 'strength' => '4%', 'forms' => json_encode(['injection']), 'default_unit' => 'ampoule', 'default_frequency' => 'Au besoin', 'default_duration_days' => 1, 'allergen_keywords' => json_encode(['sulfites']), 'contraindication_tags' => json_encode(['allergie_sulfites']), 'interaction_keywords' => json_encode([])],
        ];

        foreach ($medications as $m) {
            DB::table('medications')->insert($m + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $templateIds = [];
        $templates = [
            ['code' => 'EXTRACTION_SAGESSE', 'name' => 'Extraction dent de sagesse', 'context' => 'Chirurgie orale', 'notes' => 'Antalgique + antiseptique + ATB selon cas'],
            ['code' => 'PROTO_IMPLANTO', 'name' => 'Protocole Implantologie', 'context' => 'Implantologie', 'notes' => 'Couverture antibiotique et bain de bouche'],
            ['code' => 'URGENCE_ABCES', 'name' => 'Urgence Abces', 'context' => 'Urgence dentaire', 'notes' => 'Antibiotherapie + antalgique'],
        ];

        foreach ($templates as $t) {
            $id = DB::table('prescription_templates')->insertGetId($t + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            $templateIds[$t['code']] = $id;
        }

        $medMap = DB::table('medications')->pluck('id', 'name');

        $items = [
            ['template' => 'EXTRACTION_SAGESSE', 'med' => 'Paracetamol', 'dosage' => '1', 'unit' => 'comprime', 'frequency' => 'Matin/Midi/Soir', 'duration_days' => 3, 'instructions' => 'Apres repas'],
            ['template' => 'EXTRACTION_SAGESSE', 'med' => 'Chlorhexidine', 'dosage' => '10 ml', 'unit' => 'pulverisation', 'frequency' => 'Matin/Soir', 'duration_days' => 7, 'instructions' => 'Bain de bouche sans avaler'],
            ['template' => 'PROTO_IMPLANTO', 'med' => 'Amoxicilline', 'dosage' => '1', 'unit' => 'gelule', 'frequency' => 'Matin/Midi/Soir', 'duration_days' => 7, 'instructions' => 'Commencer 1h avant acte'],
            ['template' => 'PROTO_IMPLANTO', 'med' => 'Paracetamol', 'dosage' => '1', 'unit' => 'comprime', 'frequency' => 'Matin/Midi/Soir', 'duration_days' => 3, 'instructions' => 'En cas de douleur'],
            ['template' => 'URGENCE_ABCES', 'med' => 'Amoxicilline', 'dosage' => '1', 'unit' => 'gelule', 'frequency' => 'Matin/Midi/Soir', 'duration_days' => 7, 'instructions' => 'Respecter la duree totale'],
            ['template' => 'URGENCE_ABCES', 'med' => 'Ibuprofene', 'dosage' => '1', 'unit' => 'comprime', 'frequency' => 'Matin/Midi/Soir', 'duration_days' => 3, 'instructions' => 'Apres repas'],
        ];

        foreach ($items as $i) {
            DB::table('prescription_template_items')->insert([
                'prescription_template_id' => $templateIds[$i['template']],
                'medication_id' => $medMap[$i['med']] ?? null,
                'medication_name' => $i['med'],
                'dosage' => $i['dosage'],
                'unit' => $i['unit'],
                'frequency' => $i['frequency'],
                'duration_days' => $i['duration_days'],
                'instructions' => $i['instructions'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('prescription_template_items');
        Schema::dropIfExists('prescription_templates');
        Schema::dropIfExists('medications');
    }
};
