<?php

namespace Modules\Scheduling\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Models\Appointment;
use Modules\Scheduling\Models\AppointmentType;
use Modules\Scheduling\Models\AvailabilityBlock;
use Modules\Scheduling\Services\AvailabilityService;
use Modules\Scheduling\Services\BookingService;
use Modules\Scheduling\Services\MultiSpecialtyCoordinationService;

class ApiSchedulingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly BookingService $bookingService,
        private readonly MultiSpecialtyCoordinationService $coordinationService
    ) {}

    /**
     * GET /api/scheduling/availability
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'practitioner_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'appointment_type_id' => 'nullable|integer|exists:appointment_types,id',
        ]);

        $result = $this->availabilityService->getAvailability(
            (int) $validated['practitioner_id'],
            Carbon::parse($validated['date']),
            $validated['appointment_type_id'] ?? null
        );

        return response()->json($result);
    }

    /**
     * GET /api/scheduling/availability/range
     */
    public function availabilityRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'practitioner_id' => 'required|integer|exists:users,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'appointment_type_id' => 'nullable|integer|exists:appointment_types,id',
        ]);

        $result = $this->availabilityService->findAvailableSlotsInRange(
            (int) $validated['practitioner_id'],
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date']),
            $validated['appointment_type_id'] ?? null
        );

        return response()->json(['dates' => $result]);
    }

    /**
     * POST /api/scheduling/appointments
     */
    public function storeAppointment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'professional_id' => 'required|integer|exists:users,id',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:30',
            'patient_email' => 'nullable|email|max:255',
            'appointment_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'appointment_type_id' => 'nullable|integer|exists:appointment_types,id',
            'parent_appointment_id' => 'nullable|integer|exists:appointments,id',
            'secretary_id' => 'nullable|integer|exists:users,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $appointment = $this->bookingService->bookAppointment(
                $validated,
                auth()->id() ?? 0
            );

            return response()->json([
                'success' => true,
                'appointment' => $appointment->load(['patient', 'professional', 'appointmentType', 'room']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PATCH /api/scheduling/appointments/{appointment}/cancel
     */
    public function cancelAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $appointment = $this->bookingService->cancelAppointment(
                $appointment,
                auth()->id() ?? 0,
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'appointment' => $appointment->load(['patient', 'professional']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PATCH /api/scheduling/appointments/{appointment}/no-show
     */
    public function markNoShow(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $appointment = $this->bookingService->markNoShow(
                $appointment,
                auth()->id() ?? 0
            );

            return response()->json([
                'success' => true,
                'appointment' => $appointment->load(['patient', 'professional']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/scheduling/coordination/find-optimal-day
     */
    public function findOptimalDay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'specialties' => 'required|array|min:1',
            'specialties.*.specialty_id' => 'required|integer|exists:specialties,id',
            'specialties.*.appointment_type_id' => 'nullable|integer|exists:appointment_types,id',
            'specialties.*.priority' => 'nullable|integer|min:1',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $result = $this->coordinationService->findOptimalDayForMultipleSpecialties(
            patientId: $validated['patient_id'],
            requiredSpecialties: $validated['specialties'],
            fromDate: Carbon::parse($validated['from_date']),
            toDate: Carbon::parse($validated['to_date'])
        );

        return response()->json([
            'success' => (bool) $result,
            'optimal_day' => $result,
        ]);
    }

    /**
     * POST /api/scheduling/coordination/book-grouped
     */
    public function bookGrouped(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'date' => 'required|date',
            'specialties' => 'required|array|min:1',
            'specialties.*.specialty_id' => 'required|integer|exists:specialties,id',
            'specialties.*.appointment_type_id' => 'nullable|integer|exists:appointment_types,id',
        ]);

        try {
            $appointments = $this->coordinationService->bookGroupedAppointments(
                (int) $validated['patient_id'],
                $validated['specialties'],
                Carbon::parse($validated['date']),
                auth()->id() ?? 0
            );

            return response()->json([
                'success' => true,
                'appointments' => collect($appointments)->map->only([
                    'id',
                    'professional_id',
                    'patient_id',
                    'appointment_type_id',
                    'appointment_date',
                    'start_time',
                    'end_time',
                    'room_id',
                    'status',
                ])->values(),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/scheduling/appointment-types
     */
    public function getAppointmentTypes(Request $request): JsonResponse
    {
        $query = AppointmentType::with('specialty')->active();

        if ($request->filled('specialty_id')) {
            $query->forSpecialty((int) $request->input('specialty_id'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $types = $query->orderBy('name')->get();

        return response()->json(['types' => $types]);
    }

    /**
     * POST /api/scheduling/appointment-types
     */
    public function storeAppointmentType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'specialty_id' => 'required|integer|exists:specialties,id',
            'code' => 'required|string|unique:appointment_types,code',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'base_price' => 'nullable|numeric|min:0',
            'requires_follow_up' => 'boolean',
            'follow_up_days' => 'nullable|integer|min:1',
            'required_equipment' => 'nullable|array',
            'required_material' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $type = AppointmentType::create($validated);

        return response()->json(['success' => true, 'type' => $type], 201);
    }

    /**
     * GET /api/scheduling/rooms/available
     */
    public function availableRooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'practitioner_id' => 'nullable|integer|exists:users,id',
        ]);

        $rooms = Room::active()->get()->filter(function ($room) use ($validated) {
            return $room->isAvailableAt(
                $validated['date'],
                $validated['start_time'],
                $validated['end_time']
            );
        });

        return response()->json(['rooms' => $rooms->values()]);
    }
}
