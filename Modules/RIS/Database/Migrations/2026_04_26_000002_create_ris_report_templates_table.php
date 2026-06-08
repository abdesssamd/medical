<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ris_report_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->longText('content');
            $table->timestamps();
        });

        DB::table('ris_report_templates')->insert([
            [
                'title' => 'Panoramique dentaire standard',
                'content' => "Motif:\n\nTechnique:\nPanoramique dentaire realisee en incidence standard.\n\nResultats:\n- Structures osseuses analysees.\n- Denture et peri-apex evales.\n- Pas d'anomalie evidente sur ce cliche de depistage.\n\nConclusion:\nPanoramique sans anomalie radiologique evidente.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Cone Beam CT',
                'content' => "Motif:\n\nTechnique:\nAcquisition CBCT realisee selon protocole du secteur interesse.\n\nResultats:\n- Volumes osseux et rapports anatomiques analyses.\n- Pas de fracture ni lacune osseuse aggressive objectivable.\n- A completer selon le site et l'indication clinique.\n\nConclusion:\nAspect a confronter aux donnees cliniques.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Controle post-operatoire',
                'content' => "Motif:\nControle post-operatoire.\n\nTechnique:\nCliche de controle realise apres geste.\n\nResultats:\n- Materiel/traitement en place.\n- Absence de complication radiologique immediate visible.\n- Stabilite des structures adjacentes.\n\nConclusion:\nControle post-operatoire satisfaisant.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_report_templates');
    }
};
