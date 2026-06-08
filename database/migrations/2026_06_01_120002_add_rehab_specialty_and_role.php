<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('specialties')->where('code', 'REHAB')->exists();

        if (! $exists) {
            DB::table('specialties')->insert([
                'code' => 'REHAB',
                'name' => 'Rééducation Fonctionnelle',
                'name_ar' => 'إعادة التأهيل الوظيفي',
                'default_color' => '#8b5cf6',
                'default_duration_minutes' => 45,
                'description' => 'Spécialité dédiée à la rééducation fonctionnelle, kinésithérapie et suivi des prescriptions de réadaptation.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleExists = DB::table('roles')->where('code', 'physiotherapist')->exists();

        if (! $roleExists) {
            DB::table('roles')->insert([
                'code' => 'physiotherapist',
                'name' => 'Kinésithérapeute',
                'description' => 'Exécutant des séances de rééducation fonctionnelle.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissions = [
            ['code' => 'rehab.prescription.create', 'name' => 'Créer une prescription de rééducation', 'description' => 'Permet au médecin de créer une prescription de rééducation fonctionnelle.'],
            ['code' => 'rehab.prescription.update', 'name' => 'Modifier une prescription de rééducation', 'description' => 'Permet au médecin de modifier une prescription existante.'],
            ['code' => 'rehab.session.create', 'name' => 'Valider une séance de rééducation', 'description' => 'Permet au kinésithérapeute de valider une séance.'],
            ['code' => 'rehab.evaluation.create', 'name' => 'Créer un bilan de rééducation', 'description' => 'Permet au médecin de créer un bilan initial ou final.'],
            ['code' => 'rehab.progress.view', 'name' => 'Consulter la progression', 'description' => 'Permet de consulter les statistiques de progression.'],
        ];

        foreach ($permissions as $permission) {
            $permExists = DB::table('permissions')->where('code', $permission['code'])->exists();
            if (! $permExists) {
                DB::table('permissions')->insert(array_merge($permission, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('code', [
            'rehab.prescription.create',
            'rehab.prescription.update',
            'rehab.session.create',
            'rehab.evaluation.create',
            'rehab.progress.view',
        ])->delete();

        DB::table('roles')->where('code', 'physiotherapist')->delete();
        DB::table('specialties')->where('code', 'REHAB')->delete();
    }
};
