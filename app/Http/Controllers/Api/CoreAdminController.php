<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PractitionerAccountingProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\KpiDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoreAdminController extends Controller
{
    public function __construct(private readonly KpiDashboardService $kpiDashboardService)
    {
    }

    public function dashboardKpi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'practitioner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->endOfMonth()->toDateString();

        return response()->json(
            $this->kpiDashboardService->build([
                'from' => $from,
                'to' => $to,
                'organization_id' => $validated['organization_id'] ?? null,
                'practitioner_id' => $validated['practitioner_id'] ?? null,
            ])
        );
    }

    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_codes' => ['required', 'array', 'min:1'],
            'role_codes.*' => ['required', 'string', 'exists:roles,code'],
        ]);

        $roleIds = Role::whereIn('code', $validated['role_codes'])->pluck('id');
        $user->roles()->sync($roleIds);

        return response()->json([
            'message' => 'Roles mis a jour.',
            'user_id' => $user->id,
            'role_codes' => $user->roles()->pluck('code')->values(),
        ]);
    }

    public function assignPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*.code' => ['required', 'string', 'exists:permissions,code'],
            'permissions.*.is_granted' => ['required', 'boolean'],
        ]);

        foreach ($validated['permissions'] as $entry) {
            $permissionId = Permission::where('code', $entry['code'])->value('id');
            $user->permissions()->syncWithoutDetaching([
                $permissionId => ['is_granted' => (bool) $entry['is_granted']],
            ]);
        }

        return response()->json([
            'message' => 'Permissions utilisateur mises a jour.',
            'effective_permissions' => $user->fresh()->effectivePermissionCodes(),
        ]);
    }

    public function upsertAccountingProfile(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'entity_code' => ['nullable', 'string', 'max:40'],
            'invoice_prefix' => ['nullable', 'string', 'max:12'],
            'currency' => ['nullable', 'string', 'size:3'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $profile = PractitionerAccountingProfile::updateOrCreate(
            [
                'practitioner_id' => $user->id,
                'organization_id' => $validated['organization_id'] ?? null,
            ],
            [
                'entity_code' => $validated['entity_code'] ?? null,
                'invoice_prefix' => $validated['invoice_prefix'] ?? 'FAC',
                'currency' => $validated['currency'] ?? 'MAD',
                'default_tax_rate' => $validated['default_tax_rate'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return response()->json([
            'message' => 'Profil comptable enregistre.',
            'profile' => $profile,
        ]);
    }
}

