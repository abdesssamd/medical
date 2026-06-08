<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roles = DB::table('roles')->pluck('id', 'code');
        $permissions = DB::table('permissions')->pluck('id', 'code');

        $rolePermissions = [
            'administrator' => ['kpi.view', 'billing.manage', 'appointments.manage', 'clinical.manage', 'patient_flow.manage', 'settings.manage'],
            'practitioner' => ['kpi.view', 'appointments.manage', 'clinical.manage', 'patient_flow.manage'],
            'assistant' => ['clinical.manage', 'patient_flow.manage'],
            'secretary' => ['appointments.manage', 'patient_flow.manage'],
        ];

        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            $roleId = $roles[$roleCode] ?? null;
            if (! $roleId) {
                continue;
            }

            foreach ($permissionCodes as $permissionCode) {
                $permissionId = $permissions[$permissionCode] ?? null;
                if (! $permissionId) {
                    continue;
                }

                $exists = DB::table('permission_role')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $users = DB::table('users')->select('id', 'role')->get();
        foreach ($users as $user) {
            $mappedRoleCode = match ($user->role) {
                'super_admin', 'admin' => 'administrator',
                'professional', 'doctor' => 'practitioner',
                'assistant' => 'assistant',
                'secretary' => 'secretary',
                default => null,
            };

            if (! $mappedRoleCode || ! isset($roles[$mappedRoleCode])) {
                continue;
            }

            $roleId = $roles[$mappedRoleCode];
            $exists = DB::table('role_user')
                ->where('role_id', $roleId)
                ->where('user_id', $user->id)
                ->exists();

            if (! $exists) {
                DB::table('role_user')->insert([
                    'role_id' => $roleId,
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep seeded mappings to avoid accidental access regression on rollback.
    }
};

