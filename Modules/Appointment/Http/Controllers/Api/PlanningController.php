<?php

namespace Modules\Appointment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Http\Requests\UpsertPlanningRequest;
use Modules\Appointment\Http\Resources\PlanningResource;
use Modules\Appointment\Models\Planning;
use Modules\Appointment\Models\Setting;
use Modules\Queue\Models\Service as QueueService;

class PlanningController extends Controller
{
    public function index(User $professional): JsonResponse
    {
        $plans = Planning::where('professional_id', $professional->id)
            ->orderBy('day_of_week')
            ->get();

        $setting = Setting::firstOrCreate(
            ['professional_id' => $professional->id],
            [
                'default_commission_amount' => (float) config('appointment.default_commission_amount', 20),
                'currency' => (string) config('appointment.currency', 'MAD'),
                'allow_secretary_edit' => true,
                'allow_secretary_cancel' => true,
                'timezone' => 'Europe/Paris',
                'queue_service_id' => null,
            ]
        );

        $queueServices = QueueService::query()
            ->orderBy('name')
            ->get(['id', 'name', 'organization_id', 'prefix']);

        return response()->json([
            'plannings' => PlanningResource::collection($plans),
            'settings' => $setting,
            'queue_services' => $queueServices,
        ]);
    }

    public function upsert(UpsertPlanningRequest $request, User $professional): JsonResponse
    {
        $this->ensureProfessionalCanManage($request, $professional->id);

        $payload = $request->validated();
        $plan = Planning::updateOrCreate(
            [
                'professional_id' => $professional->id,
                'day_of_week' => (int) $payload['day_of_week'],
            ],
            [
                'start_time' => $payload['start_time'].':00',
                'end_time' => $payload['end_time'].':00',
                'consultation_minutes' => (int) $payload['consultation_minutes'],
                'max_patients_per_day' => $payload['max_patients_per_day'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]
        );

        return response()->json([
            'message' => 'Planning sauvegardé.',
            'planning' => new PlanningResource($plan),
        ]);
    }

    public function updateSettings(Request $request, User $professional): JsonResponse
    {
        $this->ensureProfessionalCanManage($request, $professional->id);

        $validated = $request->validate([
            'default_commission_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'allow_secretary_edit' => ['required', 'boolean'],
            'allow_secretary_cancel' => ['required', 'boolean'],
            'timezone' => ['required', 'string', 'max:64'],
            'queue_service_id' => ['nullable', 'exists:services,id'],
        ]);

        $setting = Setting::updateOrCreate(
            ['professional_id' => $professional->id],
            $validated
        );

        return response()->json([
            'message' => 'Paramètres mis à jour.',
            'settings' => $setting,
        ]);
    }

    private function ensureProfessionalCanManage(Request $request, int $professionalId): void
    {
        $user = $request->user();
        if (! $user || $user->role !== 'professional' || (int) $user->id !== $professionalId) {
            abort(403, 'Seul le professionnel propriétaire peut gérer ce planning.');
        }
    }
}
