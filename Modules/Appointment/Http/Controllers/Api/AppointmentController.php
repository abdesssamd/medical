<?php

namespace Modules\Appointment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Actions\CreateAppointmentAction;
use Modules\Appointment\Http\Requests\StoreAppointmentRequest;
use Modules\Appointment\Http\Requests\UpdateAppointmentStatusRequest;
use Modules\Appointment\Http\Resources\AppointmentResource;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Setting;
use Modules\Appointment\Services\AppointmentStatusService;
use Modules\Appointment\Services\AvailabilityService;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly CreateAppointmentAction $createAppointmentAction,
        private readonly AppointmentStatusService $appointmentStatusService,
        private readonly AvailabilityService $availabilityService
    ) {
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'professional_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $availability = $this->availabilityService->getAvailability(
            (int) $validated['professional_id'],
            Carbon::parse($validated['date'])
        );

        return response()->json($availability);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'professional_id' => ['required', 'exists:users,id'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:booked,cancelled,consulted,no_show'],
        ]);

        $query = Appointment::with('commission')
            ->where('professional_id', $validated['professional_id']);

        if (! empty($validated['date'])) {
            $query->whereDate('appointment_date', $validated['date']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $items = $query->orderBy('appointment_date')->orderBy('start_time')->get();

        return response()->json([
            'appointments' => AppointmentResource::collection($items),
        ]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $this->ensureRole($request, ['professional', 'secretary']);

        $payload = $request->validated();
        if ($request->user()?->role === 'secretary') {
            $payload['secretary_id'] = $request->user()->id;
        }

        $appointment = $this->createAppointmentAction->execute($payload, (int) $request->user()->id);

        return response()->json([
            'message' => 'Rendez-vous créé.',
            'appointment' => new AppointmentResource($appointment),
        ], 201);
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $this->ensureRole($request, ['professional', 'secretary']);

        $status = $request->validated()['status'];
        $user = $request->user();

        if ($user?->role === 'secretary') {
            $setting = Setting::where('professional_id', $appointment->professional_id)->first();
            if ($status === 'cancelled' && $setting && ! $setting->allow_secretary_cancel) {
                abort(403, 'La secrétaire ne peut pas annuler selon les paramètres.');
            }
            if (in_array($status, ['booked', 'no_show'], true) && $setting && ! $setting->allow_secretary_edit) {
                abort(403, 'La secrétaire ne peut pas modifier ce statut selon les paramètres.');
            }
        }

        if ($status === 'consulted') {
            $appointment = $this->appointmentStatusService->markConsulted($appointment, $user?->id);
        } else {
            $appointment->update([
                'status' => $status,
                'consulted_at' => $status === 'consulted' ? now() : null,
                'secretary_id' => $user?->role === 'secretary' ? $user->id : $appointment->secretary_id,
            ]);
            $appointment = $appointment->fresh();
        }

        $appointment->load('commission');

        return response()->json([
            'message' => 'Statut mis à jour.',
            'appointment' => new AppointmentResource($appointment),
        ]);
    }

    private function ensureRole(Request $request, array $allowedRoles): void
    {
        $role = $request->user()?->role;
        if (! in_array($role, $allowedRoles, true)) {
            abort(403, 'Accès interdit.');
        }
    }
}
