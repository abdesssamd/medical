<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_granted')->default(true);
            $table->timestamps();
            $table->unique(['permission_id', 'user_id']);
        });

        Schema::create('practitioner_accounting_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_code')->nullable();
            $table->string('invoice_prefix')->default('FAC');
            $table->string('currency', 3)->default('MAD');
            $table->decimal('default_tax_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['practitioner_id', 'organization_id'], 'uniq_pract_org_accounting_profile');
        });

        DB::table('roles')->insert([
            ['code' => 'administrator', 'name' => 'Administrateur', 'description' => 'Acces total', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'practitioner', 'name' => 'Praticien', 'description' => 'Gestion clinique et planning', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'assistant', 'name' => 'Assistante', 'description' => 'Support clinique', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'secretary', 'name' => 'Secretaire', 'description' => 'Accueil et agenda', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('permissions')->insert([
            ['code' => 'kpi.view', 'name' => 'Voir KPI', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'billing.manage', 'name' => 'Gerer facturation', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'appointments.manage', 'name' => 'Gerer rendez-vous', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'clinical.manage', 'name' => 'Gerer dossier clinique', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'patient_flow.manage', 'name' => 'Gerer flux patient', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'settings.manage', 'name' => 'Gerer parametres', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_accounting_profiles');
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

